<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Music Search Form.
 */
class SearchMusicForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'search_music_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    \Drupal::logger('music_search')->debug('Building the form: @form', ['@form' => print_r($form, TRUE)]);
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Music'),
      '#placeholder' => $this->t('Enter artist, album, or song'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['btn-lg'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get the search query.
    $query = $form_state->getValue('query');

    // Redirect to a custom route to display results.
    $form_state->setRedirect('music_search.results', ['query' => $query]);
  }
}
