<?php

namespace Drupal\music_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to create an Album.
 */
class CreateAlbumForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'create_album_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album Name'),
      '#required' => TRUE,
    ];

    $form['release_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Release Date'),
    ];

    $form['total_tracks'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Tracks'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Album'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $name = $form_state->getValue('name');
    $release_date = $form_state->getValue('release_date');
    $total_tracks = $form_state->getValue('total_tracks');

    // Create a new node of type 'album'.
    $node = \Drupal\node\Entity\Node::create([
      'type' => 'album',
      'title' => $name,
      'field_release_year' => $release_date, // Assuming the Album content type has a 'field_release_date' field.
      'field_tracks_on_album' => $total_tracks, // Assuming the Album content type has a 'field_total_tracks' field.
    ]);

    // Save the node.
    $node->save();

    // Display a confirmation message.
    $this->messenger()->addMessage($this->t('Album "@name" has been created successfully.', ['@name' => $name]));

    // Optionally redirect to the created node or another page.
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
