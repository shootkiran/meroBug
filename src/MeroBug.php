<?php

namespace MeroBug;

use Throwable;
use Illuminate\Support\Str;
use MeroBug\Models\MeroBugModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class MeroBug
{

    /** @var array */
    private $blacklist = [];

    /** @var null|string */
    private $lastExceptionId;


    public function __construct()
    {
        $this->blacklist = array_map(function ($blacklist) {
            return strtolower($blacklist);
        }, config('merobug.blacklist', []));
    }

    /**
     * @param Throwable $exception
     * @param string $fileType
     * @return bool|mixed
     */
    public function handle(Throwable $exception, $fileType = 'php', array $customData = [])
    {
        if ($this->isSkipEnvironment()) {
            return false;
        }

        $data = $this->getExceptionData($exception);

        if ($this->isSkipException($data['class'])) {
            return false;
        }

        if ($this->isSleepingException($data)) {
            return false;
        }

        if ($fileType == 'javascript') {
            $data['fullUrl'] = $customData['url'];
            $data['file'] = $customData['file'];
            $data['file_type'] = $fileType;
            $data['error'] = $customData['message'];
            $data['exception'] = $customData['stack'];
            $data['line'] = $customData['line'];
            $data['class'] = null;

            $count = config('merobug.lines_count');

            if ($count > 50) {
                $count = 12;
            }

            $lines = file($data['file']);
            $data['executor'] = [];

            for ($i = -1 * abs($count); $i <= abs($count); $i++) {
                $currentLine = $data['line'] + $i;

                $index = $currentLine - 1;

                if (!array_key_exists($index, $lines)) {
                    continue;
                }

                $data['executor'][] = [
                    'line_number' => $currentLine,
                    'line' => $lines[$index],
                ];
            }

            $data['executor'] = array_filter($data['executor']);
        }

        $bugmodel = $this->logError($data);




        if ($bugmodel->id) {
            $this->setLastExceptionId($bugmodel->id);
            if (config('merobug.telegram_api_key') !== '' && config('merobug.telegram_receiver_chat_id')) {
                $this->sendTelegramMessage([
                    'chat_id' => config('merobug.telegram_receiver_chat_id'),
                    'title' =>  'Exception Occurred',
                    'url' =>  rtrim(config('app.url')) . '/advanced/issues/' . $bugmodel->id,
                    'exception' => $data['exception']
                ]);
            }
        }

        if (config('merobug.sleep') !== 0) {
            $this->addExceptionToSleep($data);
        }

        return $bugmodel;
    }

    /**
     * @return bool
     */
    public function isSkipEnvironment()
    {
        if (count(config('merobug.environments')) == 0) {
            return true;
        }
        if (in_array(App::environment(), config('merobug.environments'))) {
            return false;
        }

        return true;
    }

    /**
     * @param string|null $id
     */
    private function setLastExceptionId(?string $id)
    {
        $this->lastExceptionId = $id;
    }

    /**
     * Get the last exception id given to us by the merobug API.
     * @return string|null
     */
    public function getLastExceptionId()
    {
        return $this->lastExceptionId;
    }

    /**
     * @param Throwable $exception
     * @return array
     */
    public function getExceptionData(Throwable $exception)
    {
        $data = [];

        $data['environment'] = App::environment();
        $data['host'] = Request::server('SERVER_NAME');
        $data['method'] = Request::method();
        $data['fullUrl'] = rtrim(config('app.url'), '/') . Request::getRequestUri();
        $data['exception'] = $exception->getMessage() ?? '-';
        $data['error'] = $exception->getTraceAsString();
        $data['line'] = $exception->getLine();
        $data['file'] = $exception->getFile();
        $data['class'] = get_class($exception);
        $data['release'] = config('merobug.release', null);
        $data['storage'] = [
            'SERVER' => [
                'USER' => Request::server('USER'),
                'HTTP_USER_AGENT' => Request::server('HTTP_USER_AGENT'),
                'SERVER_PROTOCOL' => Request::server('SERVER_PROTOCOL'),
                'SERVER_SOFTWARE' => Request::server('SERVER_SOFTWARE'),
                'PHP_VERSION' => PHP_VERSION,
            ],
            'OLD' => $this->filterVariables(Request::hasSession() ? Request::old() : []),
            'COOKIE' => $this->filterVariables(Request::cookie()),
            'SESSION' => $this->filterVariables(Request::hasSession() ? Session::all() : []),
            'HEADERS' => $this->filterVariables(Request::header()),
            'PARAMETERS' => $this->filterVariables($this->filterParameterValues(Request::all()))
        ];

        $data['storage'] = array_filter($data['storage']);

        $count = config('merobug.lines_count');

        if ($count > 50) {
            $count = 12;
        }

        $lines = file($data['file']);
        $data['executor'] = [];

        if (count($lines) < $count) {
            $count = count($lines) - $data['line'];
        }

        for ($i = -1 * abs($count); $i <= abs($count); $i++) {
            $data['executor'][] = $this->getLineInfo($lines, $data['line'], $i);
        }
        $data['executor'] = array_filter($data['executor']);

        // Get project version
        $data['project_version'] = config('merobug.project_version', null);

        // to make symfony exception more readable
        if ($data['class'] == 'Symfony\Component\Debug\Exception\FatalErrorException') {
            preg_match("~^(.+)' in ~", $data['exception'], $matches);
            if (isset($matches[1])) {
                $data['exception'] = $matches[1];
            }
        }

        return $data;
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function filterParameterValues($parameters)
    {
        return collect($parameters)->map(function ($value) {
            if ($this->shouldParameterValueBeFiltered($value)) {
                return '...';
            }

            return $value;
        })->toArray();
    }

    /**
     * Determines whether the given parameter value should be filtered.
     *
     * @param mixed $value
     * @return bool
     */
    public function shouldParameterValueBeFiltered($value)
    {
        return $value instanceof UploadedFile;
    }

    /**
     * @param $variables
     * @return array
     */
    public function filterVariables($variables)
    {
        if (is_array($variables)) {
            array_walk($variables, function ($val, $key) use (&$variables) {
                if (is_array($val)) {
                    $variables[$key] = $this->filterVariables($val);
                }
                foreach ($this->blacklist as $filter) {
                    if (Str::is($filter, strtolower($key))) {
                        $variables[$key] = '***';
                    }
                }
            });

            return $variables;
        }

        return [];
    }

    /**
     * Gets information from the line.
     *
     * @param $lines
     * @param $line
     * @param $i
     *
     * @return array|void
     */
    private function getLineInfo($lines, $line, $i)
    {
        $currentLine = $line + $i;

        $index = $currentLine - 1;

        if (!array_key_exists($index, $lines)) {
            return;
        }

        return [
            'line_number' => $currentLine,
            'line' => $lines[$index],
        ];
    }

    /**
     * @param $exceptionClass
     * @return bool
     */
    public function isSkipException($exceptionClass)
    {
        return in_array($exceptionClass, config('merobug.except'));
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isSleepingException(array $data)
    {
        if (config('merobug.sleep', 0) == 0) {
            return false;
        }

        return Cache::has($this->createExceptionString($data));
    }

    /**
     * @param array $data
     * @return string
     */
    private function createExceptionString(array $data)
    {
        return 'merobug.' . Str::slug($data['host'] . '_' . $data['method'] . '_' . $data['exception'] . '_' . $data['line'] . '_' . $data['file'] . '_' . $data['class']);
    }

    /**
     * @param $exception
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface|null
     */
    private function logError($data)
    {
        return MeroBugModel::create([
            'user' => json_encode($this->getUser()),
            'environment' => $data['environment'],
            'host' => $data['host'],
            'method' => $data['method'],
            'fullUrl' => $data['fullUrl'],
            'exception' => $data['exception'],
            'error' => $data['error'],
            'line' => $data['line'],
            'file' => $data['file'],
            'class' => $data['class'],
            'release' => $data['release'],
            'storage' => json_encode($data['storage']),
            'executor' => json_encode($data['executor']),
            'project_version' => $data['project_version'],
        ]);
    }

    /**
     * @return array|null
     */
    public function getUser()
    {
        if (function_exists('auth') && (app() instanceof \Illuminate\Foundation\Application && auth()->check())) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
            $user = auth()->user();
            if ($user instanceof \Illuminate\Database\Eloquent\Model) {
                return $user->toArray();
            }
        }

        return null;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function addExceptionToSleep(array $data)
    {
        $exceptionString = $this->createExceptionString($data);

        return Cache::put($exceptionString, $exceptionString, config('merobug.sleep'));
    }
    protected function sendTelegramMessage(array $info): void
    {
        $token = config('merobug.telegram_api_key');

        $message = "🚨 *{$info['title']}* " . config('app.url') . "\n\n"
            . "📎 [View Error]({$info['url']})\n"
            . "🧾 Exception: `{$info['exception']}`";

        $payload = [
            'chat_id' => config('merobug.telegram_receiver_chat_id'),
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ];

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
        } catch (\Throwable $e) {
            // Optional: Log failure to send Telegram message
            // \Log::warning("Failed to send Telegram error alert: " . $e->getMessage());
        }
    }
}
