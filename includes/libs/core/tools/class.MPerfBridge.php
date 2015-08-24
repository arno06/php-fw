<?php
namespace core\tools
{

	use core\application\Configuration;
	use core\data\SimpleXML;
	use \SoapParam;
	use \SoapClient;


	/**
	 * Premi&egrave;re version d'une classe permettant d'utiliser le webservice MPRemote
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 0.1
	 * @package core\tools
	 * @subpackage mailperformance
	 */
	class MPerfBridge extends SoapClient
	{
		/**
		 * WSDL du service
		 */
		const REMOTE_WSDL = "http://ws.mperf.com/wsmpremote/wsmpremote.asmx?WSDL";

		/**
		 * Namespace mpremote
		 */
		const NS_MPREMOTE = "http://np6.com/webservices/";

		/**
		 * namespace soap
		 */
		const NS_SOAP = "http://schemas.xmlsoap.org/soap/envelope/";

		/**
		 * Namespace XSI
		 */
		const NS_XSI = "http://www.w3.org/2001/XMLSchema-instance";

		/**
		 * Namespace XSD
		 */
		const NS_XSD = "http://www.w3.org/2001/XMLSchema";

		/**
		 * @var string
		 */
		private $username;

		/**
		 * @var string
		 */
		private $password;

		/**
		 * @var string
		 */
		private $guid_agence;

		/**
		 * @var string
		 */
		private $guid_user;

		/**
		 * @var string
		 */
		private $real_last_request = "";

		/**
		 * @var string
		 */
		private $action;

		/**
		 * @var array
		 */
		private $parameters;

		/**
		 * @var bool
		 */
		private $useAuth = true;

		/**
		 * Constructor
		 * @param string $pLogin
		 * @param string $pPassWord
		 * @param string $pGuidAgence
		 * @param string $pGuidUser
		 */
		public function __construct($pLogin, $pPassWord, $pGuidAgence, $pGuidUser)
		{
			parent::__construct(self::REMOTE_WSDL, array("trace"=>true));

			$this->username = $pLogin;
			$this->password = $pPassWord;
			$this->guid_agence = $pGuidAgence;
			$this->guid_user = $pGuidUser;
		}

		/**
		 * Méthode de récupération de la derni&egrave;re requête enregistrée sur le webservice
		 * @param bool $pHtmlEntities
		 * @return string
		 */
		public function lastRequest($pHtmlEntities = false)
		{
			if($pHtmlEntities)
				return htmlentities($this->real_last_request);
			return $this->real_last_request;
		}

		/**
		 * @param $request
		 * @param $location
		 * @param $action
		 * @param $version
		 * @param null $one_way
		 * @return string
		 */
		public function __doRequest($request, $location, $action, $version, $one_way = null)
		{
			Configuration::$global_encoding = "UTF-8";

			$soapRequest = array("soap:Envelope"=>array(
				"xmlns:soap"=>self::NS_SOAP,
				"xmlns:xsi"=>self::NS_XSI,
				"xmlns:xsd"=>self::NS_XSD
			));

			if($this->useAuth)
			{
				$soapRequest["soap:Envelope"]["soap:Header"]= array(
					"AuthHeader"=>array(
						"xmlns"=>self::NS_MPREMOTE,
						"username"=>array("nodeValue"=>$this->username),
						"password"=>array("nodeValue"=>$this->password)
					)
				);
			}
			else
				$this->useAuth = true;

			$action_value = array("xmlns"=>self::NS_MPREMOTE);
			foreach($this->parameters as $param)
				$action_value[$param["name"]] = array("nodeValue"=>$param["value"]);

			$soapRequest["soap:Envelope"]["soap:Body"] = array($this->action=>$action_value);
			$request = SimpleXML::encode($soapRequest);

			$this->real_last_request = $request;
			return parent::__doRequest($request, $location, $action, $version);
		}

		/**
		 * getStatusDetails(Ticket)
		 *
		 * @param $pTicket
		 * @return mixed
		 */
		public function _getStatusDetails($pTicket)
		{
			$this->useAuth = false;
			$parameters = MPParamEnum::factory()
				->add("sLogin", $this->username)
				->add("sPassword", $this->password)
				->add("sTicketNumber", $pTicket);
			return $this->performAction("getStatusDetails", $parameters);
		}

		/**
		 * GetAllRecipients()
		 *
		 * @return mixed
		 */
		public function _getAllRecipients()
		{
			$parameters = MPParamEnum::factory()
				->add("sGuidAgence", $this->guid_agence)
				->add("sGuidCustomer", $this->guid_user);
			return $this->performAction("GetAllRecipients", $parameters);
		}


		/**
		 * sentMessage()
		 *
		 * @param $pName
		 * @param $pTypeCampagne
		 * @param $pFromMail
		 * @param $pFromName
		 * @param $pSubjet
		 * @param $pReplyTo
		 * @param MPRecipientList $pRecipient
		 * @return mixed
		 */
		public function _sendMessage($pName, $pTypeCampagne, $pFromMail, $pFromName, $pSubjet, $pReplyTo, $pRecipient)
		{
			$xml = '<CAMPAIGN TYPE="'.$pTypeCampagne.'" NAME="'.$pName.'">
  <PARAMETERS>
    <MAIL_FROM_LIBELLE><![CDATA['.$pFromName.']]></MAIL_FROM_LIBELLE>
    <MAIL_FROM><![CDATA['.$pFromMail.']]></MAIL_FROM>
    <CHARSET><![CDATA[iso-8859-1]]></CHARSET>
    <MAIL_SUBJECT><![CDATA['.$pSubjet.']]></MAIL_SUBJECT>
    <REPLYTO><![CDATA['.$pReplyTo.']]></REPLYTO>
    <CONFIGURATION><![CDATA[E1T1O1T0A0P0]]></CONFIGURATION>
    <BLOCS>
    </BLOCS>
    <BLOCSTXT>
    </BLOCSTXT>
  </PARAMETERS>
  '.$pRecipient->getXML().'
</CAMPAIGN>';

			$parameters = MPParamEnum::factory()
				->add("sGuidAgence", $this->guid_agence)
				->add("sGuidCustomer", $this->guid_user)
				->add("sXML", htmlentities($xml));
			return $this->performAction("sendMessage", $parameters);
		}

		/**
		 * version()
		 *
		 * @return mixed
		 */
		public function _version()
		{
			$this->useAuth = false;
			return $this->performAction("version");
		}

		/**
		 * @param $pAction
		 * @param null|MPParamEnum $pParams
		 * @return mixed
		 */
		private function performAction($pAction, MPParamEnum $pParams = null)
		{
			if(!$pParams)
				$pParams = MPParamEnum::factory();
			$this->action = $pAction;
			$this->parameters = $pParams->get();
			return $this->$pAction();
		}
	}

	/**
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 0.1
	 * @package core\tools
	 * @subpackage mailperformance
	 */
	class MPRecipientList
	{
		/**
		 * internal
		 */
		const EMAIL = "mail";

		/**
		 * internal
		 */
		const PARAMETERS = "params";

		/**
		 * @static
		 * @return MPRecipientList
		 */
		static public function factory()
		{
			return new MPRecipientList();
		}

		/**
		 * @var array
		 */
		private $list = array();

		/**
		 * Méthode d'ajout d'un destinataire
		 * @param string $pMail
		 * @param array $pParams
		 * @return MPRecipientList
		 */
		public function add($pMail, array $pParams)
		{
			$this->list[] = array(self::EMAIL=>$pMail, self::PARAMETERS=>$pParams);
			return $this;
		}

		/**
		 * @return array
		 */
		public function get()
		{
			return $this->list;
		}

		/**
		 * Formatte les destinataires au format XML tel que l'attend le webservice MPRemote
		 * @return String
		 */
		public function getXML()
		{
			$c = array("TOS"=>array(
				"TO"=>array()
			));
			foreach($this->list as &$to)
			{
				$fields = array();
				foreach($to[self::PARAMETERS] as $k=>$v)
					$fields[] = array("NAME"=>$k, "nodeValue"=>$v);
				$c["TOS"]["TO"][] =
					array("EMAIL"=>$to[self::EMAIL],
						"UNICITY"=>array("nodeValue"=>$to[self::EMAIL]),
						"FIELD"=>$fields
					);
			}
			return SimpleXML::encode($c, false);
		}
	}

	/**
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 0.1
	 * @package core\tools
	 * @subpackage mailperformance
	 */
	class MPParamEnum
	{
		/**
		 * @var SoapParam[]
		 */
		private $params = array();

		/**
		 * @static
		 * @return MPParamEnum
		 */
		static public function factory()
		{
			return new MPParamEnum();
		}

		/**
		 * @param $pName
		 * @param $pValue
		 * @return MPParamEnum
		 */
		public function add($pName, $pValue)
		{
			$this->params[] = array("value"=>$pValue, "name"=>$pName);
			return $this;
		}

		/**
		 * @return array|SoapParam[]
		 */
		public function get()
		{
			return $this->params;
		}
	}
}