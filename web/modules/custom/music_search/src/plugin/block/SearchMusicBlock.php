<?php

namespace Drupal\music_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\Form\SearchMusicForm;

/**
 * Provides a 'Search Music' block.
 *
 * @Block(
 *   id = "search_music_block",
 *   admin_label = @Translation("Search Music Block"),
 * )
 */
class SearchMusicBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    return \Drupal::formBuilder()->getForm(SearchMusicForm::class);
  }
}
