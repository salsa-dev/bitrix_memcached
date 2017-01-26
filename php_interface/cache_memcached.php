<?
class CPHPCacheMemcached implements ICacheBackend
{
	private static $obMemcached;
	private static $basedir_version = array();
	var $sid = "";
	//cache stats
	var $written = false;
	var $read = false;
	// unfortunately is not available for memcache...

	function __construct()
	{
		$this->CPHPCacheMemcached();
	}

	function CPHPCacheMemcached()
	{
		if(!is_object(self::$obMemcached))
			self::$obMemcached = new Memcached;

		if(defined("BX_MEMCACHE_PORT"))
			$port = intval(BX_MEMCACHE_PORT);
		else
			$port = 11211;

		if(!defined("BX_MEMCACHE_CONNECTED"))
		{
			// В версии 2.0.0b1, данный параметр может также определять путь до unix сокет файла, например /path/to/memcached.sock для использования сокета домена UNIX (UDS), в данном случае port должен быть установлен в 0.
			//Not to be confused with Memcache that use 'unix:///path/to/socket'
			if(self::$obMemcached->addServer(BX_MEMCACHE_HOST, $port))
			{
				define("BX_MEMCACHE_CONNECTED", true);
				register_shutdown_function(array("CPHPCacheMemcached", "close"));
			}
		}

		if(defined("BX_CACHE_SID"))
			$this->sid = BX_CACHE_SID;
		else
			$this->sid = "BX";
	}

	function close()
	{
		if(defined("BX_MEMCACHE_CONNECTED") && is_object(self::$obMemcached))
			self::$obMemcached->resetServerList();
	}

	function IsAvailable()
	{
		return defined("BX_MEMCACHE_CONNECTED");
	}

	function clean($basedir, $initdir = false, $filename = false)
	{
		if(is_object(self::$obMemcached))
		{
			if(strlen($filename))
			{
				if(!isset(self::$basedir_version[$basedir]))
					self::$basedir_version[$basedir] = self::$obMemcached->get($this->sid.$basedir);

				if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
					return true;

				if($initdir !== false)
				{
					$initdir_version = self::$obMemcached->get(self::$basedir_version[$basedir]."|".$initdir);
					if($initdir_version === false || $initdir_version === '')
						return true;
				}
				else
				{
					$initdir_version = "";
				}

				self::$obMemcached->replace(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename, "", 1);
			}
			else
			{
				if(strlen($initdir))
				{
					if(!isset(self::$basedir_version[$basedir]))
						self::$basedir_version[$basedir] = self::$obMemcached->get($this->sid.$basedir);

					if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
						return true;

					self::$obMemcached->replace(self::$basedir_version[$basedir]."|".$initdir, "", 1);
				}
				else
				{
					if(isset(self::$basedir_version[$basedir]))
						unset(self::$basedir_version[$basedir]);

					self::$obMemcached->replace($this->sid.$basedir, "", 1);
				}
			}
			return true;
		}

		return false;
	}

	function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		if(!isset(self::$basedir_version[$basedir]))
			self::$basedir_version[$basedir] = self::$obMemcached->get($this->sid.$basedir);

		if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
			return false;

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcached->get(self::$basedir_version[$basedir]."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = self::$obMemcached->get(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename);

		if($arAllVars === false || $arAllVars === '')
			return false;

		return true;
	}

	function write($arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		if(!isset(self::$basedir_version[$basedir]))
			self::$basedir_version[$basedir] = self::$obMemcached->get($this->sid.$basedir);

		if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
		{
			self::$basedir_version[$basedir] = $this->sid.md5(mt_rand());
			self::$obMemcached->set($this->sid.$basedir, self::$basedir_version[$basedir]);
		}

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcached->get(self::$basedir_version[$basedir]."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
			{
				$initdir_version = $this->sid.md5(mt_rand());
				self::$obMemcached->set(self::$basedir_version[$basedir]."|".$initdir, $initdir_version);
			}
		}
		else
		{
			$initdir_version = "";
		}

		self::$obMemcached->set(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename, $arAllVars, time()+intval($TTL));
	}

	function IsCacheExpired($path)
	{
		return false;
	}
}
