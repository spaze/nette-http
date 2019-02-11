<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Immutable representation of a URL.
 *
 * <pre>
 * scheme  user  password  host  port      path        query    fragment
 *   |      |      |        |      |        |            |         |
 * /--\   /--\ /------\ /-------\ /--\/------------\ /--------\ /------\
 * http://john:x0y17575@nette.org:8042/en/manual.php?name=param#fragment  <-- absoluteUrl
 * \______\__________________________/
 *     |               |
 *  hostUrl        authority
 * </pre>
 *
 * @property-read string $scheme
 * @property-read string $user
 * @property-read string $password
 * @property-read string $host
 * @property-read int $port
 * @property-read string $path
 * @property-read string $query
 * @property-read string $fragment
 * @property-read string $absoluteUrl
 * @property-read string $authority
 * @property-read string $hostUrl
 * @property-read array $queryParameters
 */
class UrlImmutable implements \JsonSerializable
{
	use Nette\SmartObject;

	/** @var string */
	private $scheme = '';

	/** @var string */
	private $user = '';

	/** @var string */
	private $password = '';

	/** @var string */
	private $host = '';

	/** @var int|null */
	private $port;

	/** @var string */
	private $path = '';

	/** @var array */
	private $query = [];

	/** @var string */
	private $fragment = '';


	/**
	 * @param  string|self|Url  $url
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct($url)
	{
		if ($url instanceof Url || $url instanceof self || is_string($url)) {
			$url = is_string($url) ? new Url($url) : $url;
			[$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment] = $url->export();
		} else {
			throw new Nette\InvalidArgumentException;
		}

		if ($this->host && substr($this->path, 0, 1) !== '/') {
			$this->path = '/' . $this->path;
		}
	}


	public function getScheme(): string
	{
		return $this->scheme;
	}


	public function getUser(): string
	{
		return $this->user;
	}


	public function getPassword(): string
	{
		return $this->password;
	}


	public function getHost(): string
	{
		return $this->host;
	}


	public function getDomain(int $level = 2): string
	{
		$parts = ip2long($this->host) ? [$this->host] : explode('.', $this->host);
		$parts = $level >= 0 ? array_slice($parts, -$level) : array_slice($parts, 0, $level);
		return implode('.', $parts);
	}


	public function getPort(): ?int
	{
		return $this->port ?: (Url::$defaultPorts[$this->scheme] ?? null);
	}


	public function getPath(): string
	{
		return $this->path;
	}


	public function getQuery(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	public function getQueryParameters(): array
	{
		return $this->query;
	}


	/**
	 * @return array|string|null
	 */
	public function getQueryParameter(string $name)
	{
		return $this->query[$name] ?? null;
	}


	public function getFragment(): string
	{
		return $this->fragment;
	}


	/**
	 * Returns the entire URI including query string and fragment.
	 */
	public function getAbsoluteUrl(): string
	{
		return $this->getHostUrl() . $this->path
			. (($tmp = $this->getQuery()) ? '?' . $tmp : '')
			. ($this->fragment === '' ? '' : '#' . $this->fragment);
	}


	/**
	 * Returns the [user[:pass]@]host[:port] part of URI.
	 */
	public function getAuthority(): string
	{
		return $this->host === ''
			? ''
			: ($this->user !== ''
				? rawurlencode($this->user) . ($this->password === '' ? '' : ':' . rawurlencode($this->password)) . '@'
				: '')
			. $this->host
			. ($this->port && (!isset(Url::$defaultPorts[$this->scheme]) || $this->port !== Url::$defaultPorts[$this->scheme])
				? ':' . $this->port
				: '');
	}


	/**
	 * Returns the scheme and authority part of URI.
	 */
	public function getHostUrl(): string
	{
		return ($this->scheme ? $this->scheme . ':' : '')
			. (($authority = $this->getAuthority()) ? '//' . $authority : '');
	}


	public function __toString(): string
	{
		return $this->getAbsoluteUrl();
	}


	/**
	 * @param  string|Url|self  $url
	 */
	public function isEqual($url): bool
	{
		return (new Url($this))->isEqual($url);
	}


	public function jsonSerialize(): string
	{
		return $this->getAbsoluteUrl();
	}


	/** @internal */
	final public function export(): array
	{
		return [$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment];
	}
}
