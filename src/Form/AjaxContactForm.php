<?php

namespace Drupal\ajax_form_example\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class AjaxContactForm.
 */
class AjaxContactForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_form_example_contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['first_name'] = [
      '#prefix' => '<div class="row"><div class="col-12 col-md-6 mb-4">',
      '#type' => 'textfield',
      '#weight' => '0',
      '#required' => true,
      '#attributes' => array('placeholder' => t('First name *'), 'class' => array('form-control')),
      '#suffix' => '</div>'
    ];
    $form['last_name'] = [
      '#prefix' => '<div class="col-12 col-md-6 mb-4">',
      '#type' => 'textfield',
      '#weight' => '10',
      '#required' => true,
      '#attributes' => array('placeholder' => t('Last name *'), 'class' => array('form-control')),
      '#suffix' => '  </div></div>'
    ];
    $form['email'] = [
      '#prefix' => '<div class="row"><div class="col-12 col-md-12 mb-4">',
      '#type' => 'email',
      '#weight' => '20',
      '#required' => true,
      '#attributes' => array('placeholder' => t('Your email *'), 'class' => array('form-control')),
      '#suffix' => '</div></div>'
    ];
    $form['request_type'] = [
      '#prefix' => '<div class="row"><div class="col-12 mb-4">',
      '#type' => 'select',
      '#required' => true,
      '#options' => array(
        '' => t('How can we help you? *'),
        'login' => t('I can\'t login'),
        'demo' => t('I\'d like a live demo'),
        'sales' => t('I\'d like to contact sales before I sign up'),
        'email_notifications' => t('I don\'t receive email notifications '),
        'feature_request' => t('I have a feature request'),
        'billing_request' => t('I have a billing question'),
        'bug_report' => t('I\'d like to report a bug'),
      ),
      '#attributes' => array('class' => array('form-control')),
      '#weight' => '40',
      '#suffix' => '</div></div>'
    ];

    $form['message'] = [
      '#prefix' => '<div class="row"><div class="col-12 mb-4">',
      '#type' => 'textarea',
      '#required' => true,
      '#weight' => '50',
      '#attributes' => array('placeholder' => t('Please enter your request, comment or question *'), 'class' => array('form-control')),
      '#suffix' => '</div></div>'
    ];

    $form['actions'] = [
      '#prefix' => '<div class="text-center">',
      '#type' => 'button',
      '#weight' => '70',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Submit Support Request'),
      '#ajax' => [
        'callback' => '::validateEmailAjax',
      ],
      '#suffix' => '<span class="contact-form-result-message"></span></div>'
    ];

    $form['#attached']['library']= [
      'core/jquery',
      'core/drupal.ajax',
      'core/jquery.once',
      'core/jquery.form',
    ];

    // honeypot_add_form_protection($form, $form_state, array('honeypot', 'time_restriction'));
    return $form;
  }


  /**
   * Ajax callback to validate / send.
   */
  public function validateEmailAjax(array &$form, FormStateInterface $form_state) {

    // Initiate response.
    $response = new AjaxResponse();

    // Get vars.
    $first_name = Html::escape($form_state->getValue('first_name'));
    $last_name = Html::escape($form_state->getValue('last_name'));
    $email = Html::escape($form_state->getValue('email'));
    $request_type = Html::escape($form_state->getValue('request_type'));
    $message = $form_state->getValue('message');

    // Validate and response.
    $valid = $this->validateEmail($email);

    /** SUCCESS */
    if ($valid && !empty($first_name) && !empty($last_name) && !empty($request_type) && !empty($message)) {

      // Build mail body.
      $mail_body = $first_name;
      $mail_body .= " | ";
      $mail_body .= $last_name;
      $mail_body .= " | ";
      $mail_body .= $email;
      $mail_body .= " | ";
      $mail_body .= $request_type;
      $mail_body .= " | ";
      $mail_body .= $message;

      // Mail it.
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'ajax_form_example';
      $key = 'public_form_submission';
      $to = \Drupal::config('system.site')->get('mail');
      $params['message'] = $mail_body;
      $params['title'] = $first_name .' ' .$last_name;
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = true;

      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      if ($result['result'] !== true) {
        $message = $this->t('There was a problem sending your message, it was NOT sent, please contact us by email or phone.');
        $response->addCommand(new HtmlCommand('.contact-form-result-message', $message));
      }
      else {
        // Success, Ajax feedback.
        // Make email green, for if it was red before.
        $css = ['border' => '1px solid #ced4da'];
        $response->addCommand(new CssCommand('#edit-email', $css));
        // Make message green, for if it was red.
        $css2 = ['color' => 'green'];
        $response->addCommand(new CssCommand('.contact-form-result-message', $css2));
        // Empty all fields, flood control.
        $response->addCommand(new InvokeCommand('#edit-first-name', 'val', ['']));
        $response->addCommand(new InvokeCommand('#edit-last-name', 'val', ['']));
        $response->addCommand(new InvokeCommand('#edit-email', 'val', ['']));
        $response->addCommand(new InvokeCommand('#edit-message', 'val', ['']));
        $response->addCommand(new InvokeCommand('#edit-request_type', 'val', ['']));
        // Success message.
        $message = $this->t('Thanks! We\'ll contact you asap.');
        $response->addCommand(new HtmlCommand('.contact-form-result-message', $message));
      }
    }

    /** FIELD EMPTY */
    elseif (empty($first_name) || empty($last_name) || empty($request_type) || empty($message)) {
      $message = $this->t('Please fill out all fields.');
      $css = ['color' => 'red'];
      $response->addCommand(new CssCommand('.contact-form-result-message', $css));
      $response->addCommand(new HtmlCommand('.contact-form-result-message', $message));
    }

    /** EMAIL INVALID */
    elseif(!$valid) {
      $css = ['border' => '2px solid red'];
      $css2 = ['color' => 'red'];
      $message = $this->t('Please enter a valid email address.');
      $response->addCommand(new CssCommand('#edit-email', $css));
      $response->addCommand(new CssCommand('.contact-form-result-message', $css2));
      $response->addCommand(new HtmlCommand('.contact-form-result-message', $message));
    }
    \Drupal::messenger()->deleteAll();
    return $response;
  }

  /**
   * @param $email
   * @return bool
   */
  protected function validateEmail($email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing needed here, but mandatory by Interface.
  }

}
