<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to create an Artist.
 */
class CreateArtistForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_artist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist Name'),
      '#required' => TRUE,
    ];

    $form['genres'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Genres'),
      '#description' => $this->t('Enter genres separated by commas.'),
    ];

    $form['followers'] = [
      '#type' => 'number',
      '#title' => $this->t('Followers'),
      '#description' => $this->t('Number of followers.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Artist'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $genres = $form_state->getValue('genres');
    $followers = $form_state->getValue('followers');

    // Create a new node of type 'artist'.
    $node = \Drupal\node\Entity\Node::create([
      'type' => 'artist',
      'title' => $name,
      'field_genres' => $genres, // Assuming the Artist content type has a 'field_genres' field.
      'field_followers' => $followers, // Assuming the Artist content type has a 'field_followers' field.
    ]);

    // Save the node.
    $node->save();

    // Display a confirmation message.
    $this->messenger()->addMessage($this->t('Artist "@name" has been created successfully.', ['@name' => $name]));

    // Optionally redirect to the created node or another page.
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
