<?php

namespace Drupal\site_api_key\Form;

use Drupal\system\Form\SiteInformationForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;

/**
 * Class ExtendedSiteInformationForm.
 */
class ExtendedSiteInformationForm extends SiteInformationForm {

  /**
   * The messenger Service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ExtendedSiteInformationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   * @param \Drupal\Core\Routing\RequestContext $request_context
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context, Messenger $messenger) {
    parent::__construct($config_factory, $alias_manager, $path_validator, $request_context);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the system.site configuration.
    $site_config = $this->config('system.site');

    // Get the original form from the class we are extending.
    $form = parent::buildForm($form, $form_state);

    // Add a text field to the site information section of the form for our
    // site api key.
    $form['site_information']['siteapikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site API Key'),
      '#attributes' => [
        'placeholder' => $this->t('No API Key yet'),
      ],
      '#default_value' => $site_config->get('siteapikey') ?: '',
      '#description' => $this->t("Custom field to set the API Key"),
    ];

    if (!empty($site_config->get('siteapikey'))) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update configuration'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Foreach ($form_state->getValues() as $key => $value) {.
    // @TODO: Validate fields.
    // }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Now we need to save the new site api key to the
    // system.site.siteapikey configuration.
    $this->config('system.site')
      // The api key is retrieved from the submitted form values
      // and saved to the 'siteapikey' element of the system.site configuration.
      ->set('siteapikey', $form_state->getValue('siteapikey'))

      // Make sure to save the configuration.
      ->save();

    if (!empty($form_state->getValue('siteapikey'))) {
      \Drupal::messenger()
        ->addMessage($this->t('Site API Key has been saved with the value @siteapikey.', ['@siteapikey' => $form_state->getValue('siteapikey')]));
    }

    // Pass the remaining values off to the original form that we have extended,
    // so that they are also saved.

    parent::submitForm($form, $form_state);
  }

}
