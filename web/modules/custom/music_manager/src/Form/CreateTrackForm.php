<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to create a Track.
 */
class CreateTrackForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_track_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Track Name'),
      '#required' => TRUE,
    ];

    $form['album'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album'),
    ];

    $form['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration (seconds)'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Track'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $album = $form_state->getValue('album');
    $duration = $form_state->getValue('duration');

    // Create a new node of type 'track'.
    $node = \Drupal\node\Entity\Node::create([
      'type' => 'track',
      'title' => $name,
      'field_album' => $album, // Assuming the Track content type has a 'field_album' field.
      'field_length' => $duration, // Assuming the Track content type has a 'field_duration' field.
    ]);

    // Save the node.
    $node->save();

    // Display a confirmation message.
    $this->messenger()->addMessage($this->t('Track "@name" has been created successfully.', ['@name' => $name]));

    // Optionally redirect to the created node or another page.
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
