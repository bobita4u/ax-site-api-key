<?php

namespace Drupal\site_api_key\Form;

use Drupal\system\Form\SiteInformationForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ExtendedSiteInformationForm.
 */
class ExtendedSiteInformationForm extends SiteInformationForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the system.site configuration.
    $site_config = $this->config('system.site');
    //kint($site_config);

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
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()
        ->addMessage($key . ': ' . ($key === 'text_format' ? $value['value'] : $value));
    }

    // Now we need to save the new site api key to the
    // system.site.siteapikey configuration.
    $this->config('system.site')
      // The api key is retrieved from the submitted form values
      // and saved to the 'siteapikey' element of the system.site configuration.
      ->set('siteapikey', $form_state->getValue('siteapikey'))
      // Make sure to save the configuration
      ->save();

    // Pass the remaining values off to the original form that we have extended,
    // so that they are also saved
    parent::submitForm($form, $form_state);
  }

}
