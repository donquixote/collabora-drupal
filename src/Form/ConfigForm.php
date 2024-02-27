<?php

namespace Drupal\collabora_online\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

    const SETTINGS = 'collabora_online.settings';

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'collabora_configform';
    }

    /**
     * {@inheritdoc}
     */
    public function getEditableConfigNames() {
        return [
            static::SETTINGS
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config(static::SETTINGS);

        $form['server'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Collabora Online server URL'),
            '#default_value' => $config->get('collabora')['server'],
            '#required' => TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config(static::SETTINGS)
            ->set('collabora.server', $form_state->getValue('server'))
            ->save();

        parent::submitForm($form, $form_state);
  }
}
