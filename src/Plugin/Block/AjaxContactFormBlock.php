<?php

namespace Drupal\ajax_form_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'AjaxContactFormBlock' block.
 *
 * @Block(
 *  id = "ajax_form_example_contact_form_block",
 *  admin_label = @Translation("Ajax contact form block"),
 * )
 */
class AjaxContactFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm(\Drupal\ajax_form_example\Form\AjaxContactForm::class);

    // Build build.
    $theme_vars = [
      'contact_form' => $form,
    ];
    $build = [
      '#theme' => 'ajax_contact_form',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

}
