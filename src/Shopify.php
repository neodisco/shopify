<?php

namespace Dan\Shopify;

use BadMethodCallException;
use Dan\Shopify\Models\AbstractModel;
use Dan\Shopify\Models\Product;
use Dan\Shopify\Models\Order;
use Dan\Shopify\Models\Theme;
use Dan\Shopify\Exceptions\ModelNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class Shopify
 *
 * @property \Dan\Shopify\Helpers\Assets assets
 * @property \Dan\Shopify\Helpers\Fulfillments fulfillments
 * @property \Dan\Shopify\Helpers\Orders orders
 * @property \Dan\Shopify\Helpers\Products products
 * @property \Dan\Shopify\Helpers\Themes themes
 * @method \Dan\Shopify\Helpers\Themes themes(string $theme_id)
 */
class Shopify extends Client
{

    const SCOPE_READ_ANALYTICS = 'read_analytics';
    const SCOPE_READ_CHECKOUTS = 'read_checkouts';
    const SCOPE_READ_CONTENT = 'read_content';
    const SCOPE_READ_CUSTOMERS = 'read_customers';
    const SCOPE_READ_DRAFT_ORDERS = 'read_draft_orders';
    const SCOPE_READ_FULFILLMENTS = 'read_fulfillments';
    const SCOPE_READ_ORDERS = 'read_orders';
    const SCOPE_READ_PRICE_RULES = 'read_price_rules';
    const SCOPE_READ_PRODUCTS = 'read_products';
    const SCOPE_READ_REPORTS = 'read_reports';
    const SCOPE_READ_SCRIPT_TAGS = 'read_script_tags';
    const SCOPE_READ_SHIPPING = 'read_shipping';
    const SCOPE_READ_THEMES = 'read_themes';
    const SCOPE_READ_USERS = 'read_users';
    const SCOPE_WRITE_CHECKOUTS = 'write_checkouts';
    const SCOPE_WRITE_CONTENT = 'write_content';
    const SCOPE_WRITE_CUSTOMERS = 'write_customers';
    const SCOPE_WRITE_DRAFT_ORDERS = 'write_draft_orders';
    const SCOPE_WRITE_FULFILLMENTS = 'write_fulfillments';
    const SCOPE_WRITE_ORDERS = 'write_orders';
    const SCOPE_WRITE_PRICE_RULES = 'write_price_rules';
    const SCOPE_WRITE_PRODUCTS = 'write_products';
    const SCOPE_WRITE_REPORTS = 'write_reports';
    const SCOPE_WRITE_SCRIPT_TAGS = 'write_script_tags';
    const SCOPE_WRITE_SHIPPING = 'write_shipping';
    const SCOPE_WRITE_THEMES = 'write_themes';
    const SCOPE_WRITE_USERS = 'write_users';

    /** @var array $scopes */
    public static $scopes = [
        self::SCOPE_READ_ANALYTICS,
        self::SCOPE_READ_CHECKOUTS,
        self::SCOPE_READ_CONTENT,
        self::SCOPE_READ_CUSTOMERS,
        self::SCOPE_READ_DRAFT_ORDERS,
        self::SCOPE_READ_FULFILLMENTS,
        self::SCOPE_READ_ORDERS,
        self::SCOPE_READ_PRICE_RULES,
        self::SCOPE_READ_PRODUCTS,
        self::SCOPE_READ_REPORTS,
        self::SCOPE_READ_SCRIPT_TAGS,
        self::SCOPE_READ_SHIPPING,
        self::SCOPE_READ_THEMES,
        self::SCOPE_READ_USERS,
        self::SCOPE_WRITE_CHECKOUTS,
        self::SCOPE_WRITE_CONTENT,
        self::SCOPE_WRITE_CUSTOMERS,
        self::SCOPE_WRITE_DRAFT_ORDERS,
        self::SCOPE_WRITE_FULFILLMENTS,
        self::SCOPE_WRITE_ORDERS,
        self::SCOPE_WRITE_PRICE_RULES,
        self::SCOPE_WRITE_PRODUCTS,
        self::SCOPE_WRITE_REPORTS,
        self::SCOPE_WRITE_SCRIPT_TAGS,
        self::SCOPE_WRITE_SHIPPING,
        self::SCOPE_WRITE_THEMES,
        self::SCOPE_WRITE_USERS,
    ];

    /**
     * The current endpoint for the API. The default endpoint is /orders/
     *
     * @var string $endpoint
     */
    public $endpoint = 'orders';

    /** @var array $ids */
    public $ids = [];

    /** @var string $base */
    private static $base = 'admin';

    /**
     * Our list of valid Shopify endpoints.
     *
     * @var array $endpoints
     */
    private static $endpoints = [
        'orders' => 'orders',
        'products' => 'products',
        'themes' => 'themes',
        'assets' => 'themes/%s/assets',
    ];

    /** @var array $resource_helpers */
    private static $resource_models = [
        'orders' => Order::class,
        'products' => Product::class,
        'themes' => Theme::class,
        'assets' => Asset::class,
    ];

    /**
     * Shopify constructor.
     *
     * @param string $token
     * @param string $shop
     * @throws \Exception
     */
    public function __construct($shop, $token)
    {
        $base_uri = preg_replace("/(https:\/\/|http:\/\/)/", "", $shop);
        $base_uri = rtrim($base_uri, "/");
        $base_uri = str_replace('.myshopify.com', '', $base_uri);
        $base_uri = "https://{$base_uri}.myshopify.com";

        parent::__construct([
            'base_uri' => $base_uri,
            'headers'  => [
                'X-Shopify-Access-Token' => $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8;'
            ]
        ]);
    }

    /**
     * @param $token
     * @param $shop
     * @return static
     */
    public static function make($token, $shop)
    {
        return new static($token, $shop);
    }

    /**
     * Get a resource using the assigned endpoint ($this->endpoint).
     *
     * @param array $query
     * @param string $append
     * @return array
     */
    public function get($query = [], $append = '')
    {
        $response = $this->request(
            $method = 'GET',
            $uri = $this->endpoint($append),
            $options = ['query' => $query]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Post to a resource using the assigned endpoint ($this->endpoint).
     *
     * @param array|AbstractModel $payload
     * @param string  $append
     * @return array|AbstractModel
     */
    public function post($payload = [], $append = '')
    {
        return $this->post_or_put('POST', $payload, $append);
    }

    /**
     * Update a resource using the assigned endpoint ($this->endpoint).
     *
     * @param array|AbstractModel $payload
     * @param string $append
     * @return array|AbstractModel
     */
    public function put($payload = [], $append = '')
    {
        return $this->post_or_put('PUT', $payload, $append);
    }

    /**
     * @param $post_or_post
     * @param array $payload
     * @param string $append
     * @return mixed
     */
    private function post_or_put($post_or_post, $payload = [], $append = '')
    {
        $endpoint = $this->endpoint($append);

        $response = $this->request(
            $method = $post_or_post,
            $uri = $endpoint,
            $options = ['json' => $payload]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if ($payload instanceof AbstractModel) {
            if (isset($data[$payload::$resource_name])) {
                $data = $data[$payload::$resource_name];
            }

            $payload->syncOriginal($data);

            return $payload;
        }

        return $data;
    }

    /**
     * Delete a resource using the assigned endpoint ($this->endpoint).
     *
     * @param array|string $query
     * @param string $append
     * @return array
     */
    public function delete($query = [], $append = '')
    {
        $response = $this->request(
            $method = 'DELETE',
            $url = $this->endpoint($append),
            $options = ['query' => $query]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $id
     * @return AbstractModel|null
     * @throws ModelNotFoundException
     */
    public function find($id)
    {
        try {
            $data = $this->get([], $append = $id);

            if (isset(static::$resource_models[$this->endpoint])) {
                $class = static::$resource_models[$this->endpoint];

                if (isset($data[$class::$resource_name])) {
                    $data = $data[$class::$resource_name];
                }

                return empty($data) ? null : new $class($data);
            }
        } catch (ClientException $ce) {
            if ($ce->getResponse()->getStatusCode() == 404) {
                $msg = sprintf('Model(%s) not found for `%s`',
                    $id, $this->endpoint);

                throw new ModelNotFoundException($msg);
            }

            throw $ce;
        }
    }

    /**
     * Return an array of models or Collection (if Laravel present)
     *
     * @param string|array $ids
     * @param string $append
     * @return array|\Illuminate\Support\Collection
     */
    public function findMany($ids, $append = '')
    {
        if (is_array($ids)) {
            $ids = implode(',', array_filter($ids));
        }

        return $this->all(compact('ids'), $append);
    }

    /**
     * Shopify limits to 250 results
     *
     * @param array $query
     * @param string $append
     * @return array|\Illuminate\Support\Collection
     */
    public function all($query = [], $append = '')
    {
        $data = $this->get($query, $append);

        if (static::$resource_models[$this->endpoint]) {
            $class = static::$resource_models[$this->endpoint];

            if (isset($data[$class::$resource_name_many])) {
                $data = $data[$class::$resource_name_many];
            }

            $data = array_map(function($arr) use ($class) {
                return new $class($arr);
            }, $data);

            return defined('LARAVEL_START') ? collect($data) : $data;
        }

        return $data;
    }

    /**
     * Post to a resource using the assigned endpoint ($this->endpoint).
     *
     * @param AbstractModel $model
     * @param string $append
     * @return AbstractModel
     */
    public function save(AbstractModel $model, $append = '')
    {
        // Filtered by endpoint() if falsy
        $id = $model->getAttribute($model::$identifier);

        $response = $this->request(
            $method = $id ? 'PUT' : 'POST',
            $uri = $this->endpoint($append, $id),
            $options = ['json' => $model]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data[$model::$resource_name])) {
            $data = $data[$model::$resource_name];
        }

        $model->syncOriginal($data);

        return $model;
    }

    /**
     * @param AbstractModel $model
     * @return bool
     */
    public function destroy(AbstractModel $model)
    {
        $response = $this->delete($model->getOriginal($model::$identifier));

        if ($success = is_array($response) && empty($response)) {
            $model->exists = false;
        }

        return $success;
    }

    /**
     * @param array $query
     * @param string $append
     * @return integer
     */
    public function count($query = [], $append = '')
    {
        $endpoint = $this->endpoint($append, 'count');

        $response = $this->request('GET', $endpoint, ['query' => $query]);

        $data = json_decode($response->getBody()->getContents(), true);

        return count($data) == 1
            ? array_values($data)[0]
            : $data;
    }

    /**
     * @param array ...$args
     * @return string
     */
    public function endpoint(...$args)
    {
        $endpoint = vsprintf($this->endpoint, $this->ids);

        $this->ids = [];

        array_unshift($args, $endpoint);

        return call_user_func_array([get_class($this), 'makeEndpoint'], $args);
    }

    /**
     * @param array ...$args
     * @return string
     */
    private static function makeEndpoint(...$args)
    {
        $args = array_merge([static::$base], $args);

        return "/".implode('/', array_filter($args)).".json";
    }

    /**
     * Set our endpoint by accessing it via a property.
     *
     * @param string $property
     * @return $this
     */
    public function __get($property)
    {
        if (array_key_exists($property, static::$endpoints)) {
            $this->endpoint = static::$endpoints[$property];
        }

        $className = "Dan\Shopify\\Helpers\\" . ucfirst($property);

        if (class_exists($className)) {
            return new $className($this);
        }

        return $this;
    }

    /**
     * Set ids for one endpoint() call.
     *
     * @param string $method
     * @param array $parameters
     * @return $this
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (array_key_exists($method, static::$endpoints)) {
            $this->ids = array_merge($parameters);
            return $this->__get($method);
        }

        $msg = sprintf('Method %s does not exist.', $method);

        throw new BadMethodCallException($msg);
    }
}
