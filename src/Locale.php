<?php
/**
 * @author  Dmitriy Lukin <lukin.d87@gmail.com>
 */


namespace XrTools;

/**
 * Localization utilities (time, language, geo)
 */
class Locale {

	private $config;
	private array $mesCustomCache = [];

	function __construct(\XrTools\Config $config){
		$this->config = $config;
	}

	// messaging service array (lang => object)
	private $mes = [];

	// messaging service
	function mes(string $lang = null){

		$config = $this->config->get('mes') ?? [];

		if(empty($lang)){
			$lang = $config['lang'] ?? $config['default_lang'] ?? 'en';
		}
		else {
			$config['lang'] = $lang;
		}

		if(isset($this->mes[$lang])){
			return $this->mes[$lang];
		}
		
		$this->mes[$lang] = new \XrTools\Locale\Messages($config);

		return $this->mes[$lang];
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	function getMesCustom(string $name): mixed
	{
		if (isset($this->mesCustomCache[$name])) {
			return $this->mesCustomCache[$name];
		}

		$config = $this->config->get('mes') ?? null;

		if (! $config) {
			return null;
		}

		$lang = $config['lang'] ?? $config['default_lang'] ?? 'en';
		$folder = $config['default_mes_custom_folder'] . $name . '/' . $lang . '.php';

		if (! is_file($folder)) {
			return null;
		}

		$content = include $folder;

		$this->mesCustomCache[$name] = $content;

		return $content;
	}

	// geoIp service
	private $geoip; function geoip(){
		return $this->geoip ?: $this->geoip = new \XrTools\Locale\GeoIP(
			$this->config->get('geoip') ?? []
		);
	}

	/**
	 * [getTimezoneOffset description]
	 * @param  [type] $remote_tz can be obtained via this->get_user_timezone()
	 * @param  [type] $origin_tz 
	 * @return [type]            
	 */
	function getTimezoneOffset($remote_tz, $origin_tz = null) {

		if($origin_tz === null) {
			if(!is_string($origin_tz = date_default_timezone_get())) {
				// A UTC timestamp was returned -- bail out!
				return 0;
			}
		}

		$origin_dtz = new \DateTimeZone($origin_tz);
		$remote_dtz = new \DateTimeZone($remote_tz);
		
		$origin_dt = new \DateTime("now", $origin_dtz);
		$remote_dt = new \DateTime("now", $remote_dtz);
		
		$offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
		
		return $offset;
	}


	/**
	 * [getBrowserLocale description]
	 * @return [type] [description]
	 */
	function getBrowserLocale(){
		// read browser
		return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? 
			\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 
			'';
	}

	/**
	 * [getTimezone description]
	 * @param  string $country_code [description]
	 * @param  string $region_code  [description]
	 * @return [type]               [description]
	 */
	function getTimezone(string $timezone = null){
		
		return $timezone && in_array($timezone, \DateTimeZone::listIdentifiers()) ? 
			$timezone : 
			date_default_timezone_get();
	}

	function getTimezoneOffsetHoursFormat(int $seconds_offset){

		$hours_float = $seconds_offset / 3600;

		$hours = (int) $hours_float;

		$minutes = abs(($hours_float - $hours) * 60);

		$result = sprintf("%+03d:%02d", $hours, $minutes);

		return $result;
	}
}
