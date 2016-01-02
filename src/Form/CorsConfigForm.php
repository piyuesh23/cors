<?php

/**
 * @file
 * Contains \Drupal\cors\Form\CorsConfigForm.
 */

namespace Drupal\cors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CorsConfigForm extends ConfigFormBase {
  public function getFormId() {
    return 'cors_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $cors_domains = '';
    $config_cors = $this->configFactory->get('cors.config')->get('cors_domains');
    $form = array();

    foreach ($config_cors as $path => $domain) {
      $cors_domains .= $path . '|' . $domain . "\n";
    }

    $form['cors_domains'] = array(
      '#type' => 'textarea',
      '#title' => t('Domains'),
      '#description' => t('A list of paths and corresponding domains to enable for CORS. Multiple entries should be separated by a comma. Enter one value per line separated by a pipe, in this order:
     <ul>
       <li>Internal path</li>
       <li>Access-Control-Allow-Origin. Use &lt;mirror&gt; to echo back the Origin header.</li>
       <li>Access-Control-Allow-Methods</li>
       <li>Access-Control-Allow-Headers</li>
       <li>Access-Control-Allow-Credentials</li>
      </ul>
      Examples:
      <ul>
        <li>*|http://example.com</li>
        <li>api|http://example.com:8080 http://example.com</li>
        <li>api/*|&lt;mirror&gt;,https://example.com</li>
        <li>api/*|&lt;mirror&gt;|POST|Content-Type,Authorization|true</li>
      </ul>'),
      '#default_value' => $cors_domains,
      '#rows' => 10,
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cors_domains = $form_state->getValue('cors_domains', '');

    $domains = explode("\n", $cors_domains, 2);
    $settings = array();
    foreach ($domains as $domain) {
      $domain = explode("|", $domain, 2);

      if (!empty($domain[0]) || !empty($domain[1])) {
        $settings[$domain[0]] = (isset($settings[$domain[0]])) ? $settings[$domain[0]] . ' ' : '';
        $settings[$domain[0]] .= trim($domain[1]);
      }
      else {
        $form_state->setErrorByName('cors_domains', t('Malformed entry.'));
      }
    }

    if ($cors_domains) {
      $form_state->setErrorByName('cors_domains', t('No domains provided.'));
    }
    elseif ($settings) {
      $form_state->setValue('settings', $settings);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('cors.config');
    $config->set('cors_domains', $form_state->getValue('settings'));
    $config->save();

    drupal_set_message(t('Configuration saved successfully!'), 'status', FALSE);
  }

  protected function getEditableConfigNames() {
    return ['cors.config'];
  }
}
