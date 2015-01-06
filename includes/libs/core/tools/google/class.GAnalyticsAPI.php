<?php
namespace core\tools\google
{
	use core\tools\Request;
	use core\data\SimpleJSON;
	use \Exception;

	/**
	 * Class GAnalyticsAPI
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\tools
	 * @subpackage google
	 */
	class GAnalyticsAPI extends GClientLogin
	{
		/**
		 * @const string
		 */
		const SERVICE_GOOGLE_ANALYTICS = "analytics";


		/**
		 * constructor
		 * @param string $pEmail
		 * @param string $pMdp
		 * @param string $pAccountType
		 */
		public function __construct($pEmail, $pMdp, $pAccountType = self::TYPE_GOOGLE)
		{
			parent::__construct($pEmail, $pMdp, self::SERVICE_GOOGLE_ANALYTICS, $pAccountType);
		}


		/**
		 * @return GAQuery
		 */
		public function query()
		{
			return new GAQuery($this);
		}


		/**
		 * @param GAQuery $pQuery
		 * @return array
		 */
		public function retrieveData(GAQuery $pQuery)
		{
			if(!$this->isAuth())
				return null;
			$r= new Request($pQuery->get());
			$header[] = 'Authorization: GoogleLogin auth=' . $this->getAuth();
			$r->setOption(CURLOPT_SSL_VERIFYPEER, 0);
			$r->setOption(CURLOPT_HTTPHEADER, $header);
			$r->setOption(CURLOPT_HEADER, false);
			return SimpleJSON::decode($r->execute());
		}
	}

	/**
	 * Class GAQuery
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package tools
	 * @subpackage google
	 */
	class GAQuery
	{
		const URL_ANALYTICS_FEED = "https://www.google.com/analytics/feeds/data";

		const EQUAL             = "%3D%3D";

		const NOT_EQUAL         = "!%3D";

		const GREATER           = "%3E";

		const LESSER            = "%3C";

		const GREATER_EQUAL     = "%3E%3D";

		const LESSER_EQUAL      = "%3C%3D";

		const CONTAINS          = "%3D@";

		const NOT_CONTAINS      = "!@";

		const MATCH_REGEXP      = "%3D~";

		const NOT_MATCH_REGEXP  = "!~";


		/**
		 * @const array
		 */
		private $params;

		/**
		 * @const GAnalyticsAPI
		 */
		private $context;


		/**
		 * constructor
		 * @param GAnalyticsAPI $pContext
		 */
		public function __construct(GAnalyticsAPI $pContext)
		{
			$this->context = $pContext;
			$this->params = array("alt"=>"json");
		}


		/**
		 * @param string $pIds
		 * @return GAQuery
		 */
		public function ids($pIds)
		{
			if(!preg_match("/^ga\:/", $pIds, $m))
				$pIds = "ga:".$pIds;
			$this->params["ids"] = $pIds;
			return $this;
		}


		/**
		 * Méthode de définition des dimensions &agrave; récupérer, attend en param&egrave;tre la liste de ces dimensions
		 * @return GAQuery
		 */
		public function dimensions()
		{
			$param = func_get_args();
			$this->params["dimensions"] = implode(",",$param);
			return $this;
		}


		/**
		 * Méthode de définition des métrics de la requête, attend en param&egrave;tre la liste des ces metrics
		 * @return GAQuery
		 */
		public function metrics()
		{
			$param = func_get_args();
			$this->params["metrics"] = implode(",",$param);
			return $this;
		}


		/**
		 * Méthode de définition des metrics ou dimensions &agrave; prendre en compte dans le param&egrave;tre "sort" de la requête
		 * @return GAQuery
		 */
		public function sort()
		{
			$param = func_get_args();
			$this->params["sort"] = implode(",",$param);
			return $this;
		}


		/**
		 * @param GAFilter $pFilter
		 * @return GAQuery
		 */
		public function filter(GAFilter $pFilter)
		{
			$this->params["filters"] = $pFilter->get();
			return $this;
		}


		/**
		 * @param int $pFromIndex        Min 1
		 * @param int $pMaxResults
		 * @return GAQuery
		 */
		public function limit($pFromIndex, $pMaxResults)
		{
			$this->params["start-index"] = $pFromIndex;
			$this->params["max-results"] = $pMaxResults;
			return $this;
		}


		/**
		 * @param string $pStartDate       AAAA-MM-DD
		 * @param string $pEndDate         AAAA-MM-DD
		 * @return GAQuery
		 */
		public function timeLapse($pStartDate, $pEndDate)
		{
			$this->params["start-date"] = $pStartDate;
			$this->params["end-date"] = $pEndDate;
			return $this;
		}


		/**
		 * Force la requête &agrave; renvoyer des données au format JSON
		 * @return GAQuery
		 */
		public function returnJSON()
		{
			$this->params["alt"] = "json";
			return $this;
		}


		/**
		 * Force la requête &agrave; renvoyer des données au format XML
		 * @return GAQuery
		 */
		public function returnXML()
		{
			if(isset($this->params["alt"]))
				unset($this->params["alt"]);
			return $this;
		}


		/**
		 * Méthode de génération de la requête
		 * @return string
		 */
		public function get()
		{
			$query = "";
			foreach($this->params as $name=>$value)
			{
				if(!empty($query))
					$query.="&";
				$query .= $name."=".$value;
			}
			$query = self::URL_ANALYTICS_FEED."?".$query;
			return $query;
		}


		/**
		 * Méthode d'éxecution de la requête Google Analytics en cours
		 * @throws Exception
		 * @return array
		 */
		public function execute()
		{
			if(!$this->context)
				throw new Exception("Impossible d'executer unen requête Analytics sans contexte GAnalyticsAPI");
			return $this->context->retrieveData($this);
		}
	}


	/**
	 * Class GAFilter
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package tools
	 * @subpackage google
	 */
	class GAFilter
	{
		/**
		 * @const string
		 */
		const SEPARATOR_AND =   ";";

		/**
		 * @const string
		 */
		const SEPARATOR_OR  =   ",";

		/**
		 * @var string
		 */
		private $what;

		/**
		 * @var string
		 */
		private $type;

		/**
		 * @var string
		 */
		private $value;

		/**
		 * @var array
		 */
		private $orFilters = array();

		/**
		 * @var array
		 */
		private $andFilters = array();


		/**
		 * constructor
		 * @param string $pWhat
		 * @param string $pType
		 * @param string $pValue
		 */
		public function __construct($pWhat, $pType, $pValue)
		{
			$this->what = $pWhat;
			$this->type = $pType;
			$this->value = $pValue;
		}


		/**
		 * @param GAFilter $pFilter
		 * @return GAFilter
		 */
		public function andFilter(GAFilter $pFilter)
		{
			$this->andFilters[] = $pFilter->get();
			return $this;
		}


		/**
		 * @param GAFilter $pFilter
		 * @return GAFilter
		 */
		public function orFilter(GAFilter $pFilter)
		{
			$this->orFilters[] = $pFilter->get();
			return $this;
		}


		/**
		 * @return string
		 */
		public function get()
		{
			$and = implode(self::SEPARATOR_AND, $this->andFilters);
			if(!empty($and))
				$and = self::SEPARATOR_AND.$and;
			$or = implode(self::SEPARATOR_OR, $this->orFilters);
			if(!empty($or))
				$or = self::SEPARATOR_OR.$or;
			return $this->what.$this->type.$this->value.$and.$or;
		}
	}

	/**
	 * @package tools
	 * @subpackage google
	 */
	class GAMetrics
	{
		/**
		 * Total number of visitors to your website for the requested time period.
		 */
		const VISITORS                      = "ga:visitors";

		/**
		 * The number of visitors whose visit to your website was marked as a first-time visit.
		 */
		const VISITOR_NEW_VISITS            = "ga:newVisits";

		/**
		 * The percentage of visits by people who had never visited your site before. (ga:newVisits / ga:visits) * 100
		 * @var string
		 */
		const VISITOR_PERCENT_NEW_VISITS    = "ga:percentNewVisits";

		/**
		 * The total number of visits over the selected dimension. A visit consists of a single-user session.
		 */
		const VISITS                        = "ga:visits";

		/**
		 * The total duration of visitor sessions represented in total seconds. The value returned in the XML is a string and various client libraries use different types to represent this value, such as a double, float, long, or string.
		 */
		const SESSION_TIME_ON_SITE          = "ga:timeOnSite";

		/**
		 * The average duration visitor sessions represented in total seconds.
		 */
		const SESSION_AVG_TIME_ON_SITE      = "ga:avgTimeOnSite";

		/**
		 * The number of organic searches that happened within a session. This metric is search engine agnostic.
		 */
		const CAMPAIGN_ORGANIC_SEARCHES     = "ga:organicSearches";

		/**
		 * The number of entrances to your website. The value will always be equal to the number of visits when aggregated over your entire website. Typically used with ga:landingPagePath
		 */
		const TRACKING_ENTRANCES            = "ga:entrances";

		/**
		 * The percentage of pageviews in which this page was the entrance.
		 */
		const TRACKING_ENTRANCE_RATE        = "ga:entranceRate";

		/**
		 * The total number of single-page (or event) sessions to your website.
		 */
		const TRACKING_BOUNCES              = "ga:bounces";

		/**
		 * The percentage of single-page visits (i.e. visits in which the person left your site from the entrance page).
		 */
		const TRACKING_ENTRANCE_BOUNCE_RATE = "ga:entranceBounceRate";

		/**
		 * The percentage of single-page visits (i.e., visits in which the person left your site from the first page).
		 */
		const TRACKING_VISIT_BOUNCE_RATE    = "ga:visitBounceRate";

		/**
		 * The total number of pageviews for your website.
		 */
		const TRACKING_PAGEVIEWS            = "ga:pageviews";

		/**
		 * The average number of pages viewed during a visit to your site. Repeated views of a single page are counted.
		 */
		const TRACKING_PAGEVIEWS_PER_VISIT  = "ga:pageviewsPerVisit";

		/**
		 * The number of different (unique) pages within a visit, summed up across all visits
		 */
		const TRACKING_UNIQUE_PAGE_VIEWS    = "ga:uniquePageViews";

		/**
		 * How long a visitor spent on a particular page or set of pages. Calculated by subtracting the initial view time for a particular page from the initial view time for a subsequent page. Thus, this metric does not apply to exit pages for your website. The value from this metric is returned in the XML as a string, with the value represented in total seconds. Different client libraries have various ways of representing this value, such as a double, float, long, or string.
		 */
		const TRACKING_TIME_ON_PAGE         = "ga:timeOnPage";

		/**
		 * The average amount of time visitors spent viewing this page or a set of pages.
		 */
		const TRACKING_AVG_TIME_ON_PAGE     = "ga:avgTimeOnPage";

		/**
		 * The number of exits from your website. As with entrances, it will always be equal to the number of visits when aggregated over your entire website.
		 */
		const TRACKING_EXITS                = "ga:exits";

		/**
		 * The percentage of site exits that occurred out of the total page views.
		 */
		const TRACKING_EXIT_RATE            = "ga:exitRate";
	}

	/**
	 * @package tools
	 * @subpackage google
	 */
	class GADimensions
	{
		/**
		 * A boolean indicating if visitors are new or returning.Possible values: New Visitor, Returning Visitor.
		 */
		const VISITOR_TYPE                  = "ga:visitorType";

		/**
		 * Number of visits to your website. This is calculated by determining the number of visitor sessions.
		 */
		const VISITOR_COUNT                 = "ga:visitCount";

		/**
		 * The number of days elapsed since visitors last visited your website. Used to calculate visitor loyalty.
		 */
		const VISITOR_DAYS_SINCE_LAST_VISIT = "ga:daysSinceLastVisit";

		/**
		 * The value provided when you define custom visitor segments for your website.
		 */
		const VISITOR_USER_DEFINED_VALUE    = "ga:userDefinedValue";

		/**
		 * The length of a visit to your website measured in seconds and reported in second increments. The value returned is a String.
		 */
		const SESSION_VISIT_LENGTH          = "ga:visitLength";

		/**
		 * The path of the referring URL. If someone places a link to your website on their website, this element contains the path of the page that contains the referring link.
		 */
		const CAMPAIGN_REFERRAL_PATH        = "ga:referralPath";

		/**
		 * When using manual campaign tracking, the value of the utm_campaign campaign tracking parameter. When using AdWords autotagging, the name(s) of the online ad campaign that you use for your website. Otherwise the value (not set) is used.
		 */
		const CAMPAIGN_CAMPAIGN             = "ga:campaign";

		/**
		 * The source of referrals to your website.
		 */
		const CAMPAIGN_SOURCE               = "ga:source";

		/**
		 * The type of referrals to your website.
		 */
		const CAMPAIGN_MEDIUM               = "ga:medium";

		/**
		 * When using manual campaign tracking, the value of the utm_term campaign tracking parameter. When using AdWords autotagging or if a visitor used organic search to reach your website, the keywords used by visitors to reach your website. Otherwise the value is (not set).
		 */
		const CAMPAIGN_KEYWORD              = "ga:keyword";

		/**
		 * When using manual campaign tracking, the value of the utm_content campaign tracking parameter. When using AdWords autotagging, the first line of the text for your online Ad campaign. If you are using mad libs for your AdWords content, this field displays the keywords you provided for the mad libs keyword match. Otherwise the value is (not set)
		 */
		const CAMPAIGN_AD_CONTENT           = "ga:adContent";

		/**
		 * The names of browsers used by visitors to your website.
		 */
		const SYSTEM_BROWSER                = "ga:browser";

		/**
		 * The browser versions used by visitors to your website.
		 */
		const SYSTEM_BROWSER_VERSION        = "ga:browserVersion";

		/**
		 * The operating system used by your visitors.
		 */
		const SYSTEM_OS                     = "ga:operatingSystem";

		/**
		 * The version of the operating system used by your visitors.
		 */
		const SYSTEM_OS_VERSION             = "ga:operatingSystemVersion";

		/**
		 * The versions of Flash supported by visitors' browsers, including minor versions.
		 */
		const SYSTEM_FLASH_VERSION          = "ga:flashVersion";

		/**
		 * Indicates Java support for visitors' browsers. The possible values are Yes or No where the first letter must be capitalized.
		 */
		const SYSTEM_JAVA_ENABLED           = "ga:javaEnabled";

		/**
		 * Indicates mobile visitors. The possible values are Yes or No where the first letter must be capitalized.
		 */
		const SYSTEM_IS_MOBILE              = "ga:isMobile";

		/**
		 * The language provided by the HTTP Request for the browser. Values are given as an ISO-639 code (e.g. en-gb for British English).
		 */
		const SYSTEM_LANGUAGE               = "ga:language";

		/**
		 * The color depth of visitors' monitors, as retrieved from the DOM of the visitor's browser. For example 4-bit, 8-bit, 24-bit, or undefined-bit.
		 */
		const SYSTEM_SCREEN_COLORS          = "ga:screenColors";

		/**
		 * The screen resolution of visitors' monitors, as retrieved from the DOM of the visitor's browser. For example: 1024x738.
		 */
		const SYSTEM_SCREEN_RESOLUTION      = "ga:screenResolution";

		/**
		 * The continents of website visitors, derived from IP addresses.
		 */
		const GEO_CONTINENT                 = "ga:continent";

		/**
		 * The sub-continent of website visitors, derived from IP addresses. For example, Polynesia or Northern Europe.
		 */
		const GEO_SUB_CONTINENT             = "ga:subContinent";

		/**
		 * The countries of website visitors, derived from IP addresses.
		 */
		const GEO_COUNTRY                   = "ga:country";

		/**
		 * The region of website visitors, derived from IP addresses. In the U.S., a region is a state, such as New York.
		 */
		const GEO_REGION                    = "ga:region";

		/**
		 * The cities of website visitors, derived from IP addresses.
		 */
		const GEO_CITY                      = "ga:city";

		/**
		 * The approximate latitude of the visitor's city. Derived from IP address. Locations north of the equator are represented by positive values and locations south of the equator by negative values.
		 */
		const GEO_LATITUDE                  = "ga:latitude";

		/**
		 * The approximate longitude of the visitor's city. Derived from IP address. Locations east of the meridian are represented by positive values and locations west of the meridian by negative values.
		 */
		const GEO_LONGITUDE                 = "ga:longitude";

		/**
		 * The qualitative network connection speeds of website visitors. For example, T1, DSL, Cable, Dialup. Derived from IP address.
		 */
		const NETWORK_CONNECTION_SPEED      = "ga:connectionSpeed";

		/**
		 * The domain name of the ISPs used by visitors to your website. This is derived from the domain name registered to the IP address.
		 */
		const NETWORK_DOMAIN                = "ga:networkDomain";

		/**
		 * The name of service providers used to reach your website. For example, if most visitors to your website come via the major service providers for cable internet, you will see the names of those cable service providers in this element.
		 */
		const NETWORK_LOCATION              = "ga:networkLocation";

		/**
		 * The hostname from which the tracking request was made.
		 */
		const TRACKING_HOSTNAME             = "ga:hostname";

		/**
		 * A page on your website specified by path and/or query parameters. Use in conjunction with ga:hostname to get the full URL of the page.
		 */
		const TRACKING_PAGE_PATH            = "ga:pagePath";

		/**
		 * The title for a page, as specified in the document.title property of the Document Object Model. Keep in mind that multiple URLs might have the same page title.
		 */
		const TRACKING_PAGE_TITLE           = "ga:pageTitle";

		/**
		 * The path component of the first page in a user's session, or landing page.
		 */
		const TRACKING_LANDING_PAGE_PATH    = "ga:landingPagePath";

		/**
		 * The path component of the second page in a user's session.
		 */
		const TRACKING_SECOND_PAGE_PATH     = "ga:secondPagePath";

		/**
		 * The last page of the session (or "exit" page) for your visitors.
		 */
		const TRACKING_EXIT_PAGE_PATH       = "ga:exitPagePath";

		/**
		 * A page on your website that was visited before another page on your website. Typically used with the ga:nextPagePath dimension.
		 */
		const TRACKING_PREVIOUS_PAGE_PATH   = "ga:previousPagePath";

		/**
		 * A page on your website that was visited after another page on your website. Typically used with the ga:previousPagePath dimension.
		 */
		const TRACKING_NEXT_PAGE_PATH       = "ga:nextPagePath";

		/**
		 * The number of pages visited by visitors during a session (visit).
		 */
		const TRACKING_PAGE_DEPTH           = "ga:pageDepth";

		/**
		 * The date of the visit. An integer in the form YYYYMMDD.
		 */
		const TIME_DATE                     = "ga:date";

		/**
		 * The year of the visit. A four-digit year from 2005 to the current year.
		 */
		const TIME_YEAR                     = "ga:year";

		/**
		 * The month of the visit. A two digit integer from 01 to 12.
		 */
		const TIME_MONTH                    = "ga:month";

		/**
		 * The week of the visit. A two-digit number from 01 to 52.
		 */
		const TIME_WEEK                     = "ga:week";

		/**
		 * The day of the month from 01 to 31.
		 */
		const TIME_DAY                      = "ga:day";

		/**
		 * A two-digit hour of the day ranging from 00-23 in the timezone configured for the account.
		 */
		const TIME_HOUR                     = "ga:hour";

		/**
		 * Index for a month in the specified date range.
		 */
		const TIME_INDEX_MONTH              = "ga:nthMonth";

		/**
		 * Index for a week in the specified date range.
		 */
		const TIME_INDEX_WEEK               = "ga:nthWeek";

		/**
		 * Index for a day in the specified date range.
		 */
		const TIME_INDEX_DAY                = "ga:nthDay";

		/**
		 * The name for the requested custom variable.
		 */
		const CUSTOM_VAR_NAME               = "ga:customVarName";

		/**
		 * The value for the requested custom variable.
		 */
		const CUSTOM_VAR_VALUE              = "ga:customVarValue";
	}
}