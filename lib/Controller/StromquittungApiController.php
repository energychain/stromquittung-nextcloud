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
use OCP\Notification\INotification;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserBackend;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

class StromquittungApiController extends ApiController {
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

	}

  /**
	* @CORS
 	* @NoCSRFRequired
 	* @NoAdminRequired
	*/
	public function addTransaction($tx_duration,$tx_energy) {
		$this->config->setAppValue($this->appName, "tx_energy", $tx_energy);
		$this->config->setAppValue($this->appName, "tx_duration", $tx_duration);

		$response = new DataResponse([
				'userId' => 	$this->userId,
				'tx_number' => $this->config->getAppValue($this->appName, "tx_number"),
				'tx_energy' => $this->config->getAppValue($this->appName, "tx_energy"),
				'tx_duration' =>  $this->config->getAppValue($this->appName, "tx_duration")
		 ]);

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedChildSrcDomain('*');
		$policy->addAllowedFrameDomain('*');
		$response->setContentSecurityPolicy($policy);
		$manager = \OC::$server->get(\OCP\Notification\IManager::class);
		$notification = $manager->createNotification();

		$createAction = $notification->createAction();
		$createAction->setLabel('accept')
    ->setLink('stromquittung', 'GET');


		$notification->setApp('stromquittung')
		    ->setUser($this->userId)
		    ->setDateTime(new \DateTime())
		    ->setObject('stromquittung', $this->config->getAppValue($this->appName, "tx_number")) // $type and $id
		    ->setSubject('stromquittung', []) // $subject and $parameters
		    ->addAction($createAction);
		return $response;
	}

}
