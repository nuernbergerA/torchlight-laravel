<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Manager
{
    use Macroable;

    /**
     * @var null|callable
     */
    protected $getConfigUsing;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var null|string
     */
    protected $environment;

    /**
     * @param Client $client
     * @return Manager
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function client()
    {
        if (!$this->client) {
            $this->client = new Client;
        }

        return $this->client;
    }

    /**
     * @param $blocks
     * @return mixed
     */
    public function highlight($blocks)
    {
        return $this->client()->highlight($blocks);
    }

    /**
     * @return string
     */
    public function environment()
    {
        return $this->environment ?? app()->environment();
    }

    /**
     * @param string|null $environment
     */
    public function overrideEnvironment($environment = null)
    {
        $this->environment = $environment;
    }

    /**
     * Get an item out of the config using dot notation.
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        // Default to Laravel's config method.
        $method = $this->getConfigUsing ?? 'config';

        // If we are using Laravel's config method, then we'll prepend
        // the key with `torchlight` if it isn't already there.
        if ($method === 'config') {
            $key = Str::start($key, 'torchlight.');
        }

        return call_user_func($method, $key, $default);
    }

    /**
     * A callback function used to access configuration. By default this
     * is null, which will fall through to Laravel's `config` function.
     *
     * @param $callback
     */
    public function getConfigUsing($callback)
    {
        if (is_array($callback)) {
            $callback = function ($key, $default) use ($callback) {
                return Arr::get($callback, $key, $default);
            };
        }

        $this->getConfigUsing = $callback;
    }

    /**
     * Set the cache implementation directly instead of using a driver.
     *
     * @param Repository $cache
     */
    public function setCacheInstance(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * The cache store to use.
     *
     * @return Repository
     */
    public function cache()
    {
        if ($this->cache) {
            return $this->cache;
        }

        // If the developer has requested a particular store, we'll use it.
        // If the config value is null, the default cache will be used.
        return Cache::store($this->config('cache'));
    }

    /**
     * Return all the Torchlight IDs in a given string.
     *
     * @param string $content
     * @return array
     */
    public function findTorchlightIds($content)
    {
        preg_match_all('/__torchlight-block-\[(.+?)\]/', $content, $matches);

        return array_values(array_unique(Arr::get($matches, 1, [])));
    }
}
