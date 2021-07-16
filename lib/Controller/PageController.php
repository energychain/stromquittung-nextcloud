<?php
namespace OCA\StromQuittung\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\RedirectToDefaultAppResponse;
use OCP\AppFramework\App;
use OCP\AppFramework\ApiController;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserBackend;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

class PageController extends Controller {
	private $userId;
	private $storage;
	private $currentUser;
	private $config;
	// private $appName;

	public function __construct($AppName, IRequest $request, $UserId,IRootFolder $storage,IUserSession $userSession,IConfig $config){
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->userId = $UserId;
		$this->storage = $storage;
		$this->currentUser = $userSession->getUser();
		$this->appName = $AppName;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
	return $this->createResponse(15);
	}

	public function show($id) {
		$url="https://api.corrently.io/v2.0/quittung/pdf?token=".$id;

    $res = array();
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => "nextcloud",
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
    );
    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );
		$userFolder = $this->storage->getUserFolder($this->userId);
		if($this->config->getAppValue($this->appName, "tx_number") == '') {
				$this->config->setAppValue($this->appName, "tx_number", "1");
		}
		$tx_number = $this->config->getAppValue($this->appName, "tx_number");
		$fname = $tx_number.".stromquittung.pdf";


	   try {
				 $userFolder->newFile($fname);
				 $file = $userFolder->get($fname);
	       $file->putContent($content);

	   } catch(\OCP\Files\NotPermittedException $e) {
	       throw new StorageException('Cant write to file');
	   }
		 $tx_number++;
		 $this->config->setAppValue($this->appName, "tx_number", $tx_number);

		 return new DataResponse([
				 'userId' => 	$this->userId,
				 'file_name' => $fname,
				 'tx_number' => $this->config->getAppValue($this->appName, "tx_number")
	 		]);
	}

	protected function createResponse($id) {
		$seller_name = urlencode($this->currentUser->getDisplayName());
		$seller_email = urlencode($this->currentUser->getEMailAddress());
		$tx_energy = "";
		$tx_duration = "";
		if($this->config->getAppValue($this->appName, "tx_number") == '') {
			  $this->config->setAppValue($this->appName, "tx_number", "1");
		}
		if($this->config->getAppValue($this->appName, "tx_energy") !== '') {
			$tx_energy = $this->config->getAppValue($this->appName, "tx_energy");
			$this->config->setAppValue($this->appName, "tx_energy", "");
		}
		if($this->config->getAppValue($this->appName, "tx_duration") !== '') {
			$tx_duration = $this->config->getAppValue($this->appName, "tx_duration");
			$this->config->setAppValue($this->appName, "tx_duration", "");
		}
		$tx_number = urlencode($this->config->getAppValue($this->appName, "tx_number"));

		$response = new TemplateResponse('stromquittung', 'externalfrm', [
			'url' => "https://corrently.de/service/quittung.html?embed=nextcloud",
			'addquery' => '&seller_name='.$seller_name.'&seller_email='.$seller_email."&tx_energy=".$tx_energy."&tx_duration=".$tx_duration."&tx_number=".$tx_number,
			'name' => "StromQuittung",
		], 'user');

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedChildSrcDomain('*');
		$policy->addAllowedFrameDomain('*');
		$response->setContentSecurityPolicy($policy);

		return $response;
  }
}
