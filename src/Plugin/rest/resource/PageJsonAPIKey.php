<?php

namespace Drupal\site_api_key\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "page_json_apikey",
 *   label = @Translation("Page json apikey"),
 *   uri_paths = {
 *     "canonical" = "/page_json/v1/{siteapikey}/{nodeid}"
 *   }
 * )
 */
class PageJsonAPIKey extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Request Object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * The Site API Key.
   *
   * @var array|mixed|null
   */
  protected $siteApiKey;

  /**
   * PageJsonAPIKey constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   * @param \Drupal\Core\Config\ConfigManager $config_mgr
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    Request $current_request,
    ConfigManager $config_mgr) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->logger = $logger;
    $this->currentRequest = $current_request;
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_mgr;
    $this->siteApiKey = $this->configManager->getConfigFactory()
      ->get('system.site')->get('siteapikey');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('site_api_key'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param string $payload
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get($payload) {
    // Get the siteapikey & nodeid from the request url.
    $request_args = $this->currentRequest->attributes->all();

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException("You donot have permission!");
    }

    if (empty($this->siteApiKey)) {
      throw new AccessDeniedHttpException($this->t("Site API Key is not set!"));
    }

    if (!empty($this->siteApiKey) && $this->siteApiKey !== $request_args['siteapikey']) {
      throw new AccessDeniedHttpException($this->t("Site API Key is incorrect!"));
    }

    // Get the node object by NID.
    $node = $this->entityTypeManager->getStorage('node')
      ->load($request_args['nodeid']);
    $node_data = [];

    if ($node->getType() !== 'page') {
      throw new AccessDeniedHttpException($this->t("No Content."));
    }
    else {
      foreach ($node->getFields() as $id => $field) {
        $node_data[$id] = $field->getValue();
      }
    }

    $response = new ResourceResponse($node_data, Response::HTTP_OK);

    if ($this->siteApiKey) {
      $response->addCacheableDependency($node);
    }

    return $response;
  }

}
