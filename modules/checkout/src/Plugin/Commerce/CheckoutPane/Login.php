<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the login pane.
 *
 * @CommerceCheckoutPane(
 *   id = "login",
 *   label = @Translation("Login or continue as guest"),
 *   default_step = "login",
 * )
 */
class Login extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The credentials check flood controller.
   *
   * @var \Drupal\commerce\CredentialsCheckFloodInterface
   */
  protected $credentialsCheckFlood;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The client IP address.
   *
   * @var string
   */
  protected $clientIp;

  /**
   * Constructs a new Login object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, UserAuthInterface $user_auth, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->credentialsCheckFlood = $credentials_check_flood;
    $this->currentUser = $current_user;
    $this->userAuth = $user_auth;
    $this->clientIp = $request_stack->getCurrentRequest()->getClientIp();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('user.auth'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_guest_checkout' => TRUE,
      'allow_registration' => FALSE,
      'email_only' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['allow_guest_checkout'])) {
      $summary = $this->t('Guest checkout: Allowed') . '<br>';
    }
    else {
      $summary = $this->t('Guest checkout: Not allowed') . '<br>';
    }
    if (!empty($this->configuration['allow_registration'])) {
      $summary .= $this->t('Registration: Allowed');
    }
    else {
      $summary .= $this->t('Registration: Not allowed');
    }

    if (!empty($this->configuration['email_only'])) {
      $summary .= '<br>' . $this->t('Use E-mail only for registration and login');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['allow_guest_checkout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow guest checkout'),
      '#default_value' => $this->configuration['allow_guest_checkout'],
    ];
    $form['allow_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow registration'),
      '#default_value' => $this->configuration['allow_registration'],
    ];

    $form['email_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use E-Mail only for registration and login'),
      '#description' => $this->t('If checked, the name field will not be shown and the e-mail will be used as username.'),
      '#default_value' => $this->configuration['email_only'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['allow_guest_checkout'] = !empty($values['allow_guest_checkout']);
      $this->configuration['allow_registration'] = !empty($values['allow_registration']);
      $this->configuration['email_only'] = !empty($values['email_only']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    return $this->currentUser->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#attached']['library'][] = 'commerce_checkout/login_pane';

    $pane_form['returning_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Returning Customer'),
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__returning-customer',
        ],
      ],
    ];
    $pane_form['returning_customer']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->configuration['email_only'] ? $this->t('E-Mail') : $this->t('Username'),
      '#size' => 60,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];
    $pane_form['returning_customer']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
    ];
    $pane_form['returning_customer']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#op' => 'login',
      '#attributes' => [
        'formnovalidate' => 'formnovalidate',
      ],
      '#limit_validation_errors' => [
        array_merge($pane_form['#parents'], ['returning_customer']),
      ],
      '#submit' => [],
    ];
    $pane_form['returning_customer']['forgot_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Forgot password?'),
      '#url' => Url::fromRoute('user.pass'),
    ];

    $pane_form['guest'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Guest Checkout'),
      '#access' => $this->configuration['allow_guest_checkout'],
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__guest-checkout',
        ],
      ],
    ];
    $pane_form['guest']['text'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Proceed to checkout. You can optionally create an account at the end.'),
      '#access' => $this->canRegisterAfterCheckout(),
    ];
    $pane_form['guest']['continue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue as Guest'),
      '#op' => 'continue',
      '#attributes' => [
        'formnovalidate' => 'formnovalidate',
      ],
      '#limit_validation_errors' => [],
      '#submit' => [],
    ];

    $pane_form['register'] = [
      '#parents' => array_merge($pane_form['#parents'], ['register']),
      '#type' => 'fieldset',
      '#title' => $this->t('New Customer'),
      '#access' => $this->configuration['allow_registration'],
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__guest-checkout',
        ],
      ],
    ];
    $pane_form['register']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => FALSE,
    ];
    $pane_form['register']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => FALSE,
      '#access' => empty($this->configuration['email_only']),
      '#attributes' => [
        'class' => ['username'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => '',
    ];
    $pane_form['register']['password'] = [
      '#type' => 'password_confirm',
      '#size' => 60,
      '#description' => $this->t('Provide a password for the new account in both fields.'),
      '#required' => FALSE,
    ];
    $pane_form['register']['register'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create account and continue'),
      '#op' => 'register',
      '#weight' => 50,
    ];

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->create([]);
    $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
    $form_display->buildForm($account, $pane_form['register'], $form_state);

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $triggering_element = $form_state->getTriggeringElement();
    $trigger = !empty($triggering_element['#op']) ? $triggering_element['#op'] : 'continue';
    switch ($trigger) {
      case 'continue':
        return;

      case 'login':
        $name_element = $pane_form['returning_customer']['name'];
        $username = $values['returning_customer']['name'];
        $password = trim($values['returning_customer']['password']);
        // Generate the "reset password" url.
        $query = !empty($username) ? ['name' => $username] : [];
        $password_url = Url::fromRoute('user.pass', [], ['query' => $query])->toString();

        if (empty($username) || empty($password)) {
          $form_state->setError($pane_form['returning_customer'], $this->t('Unrecognized username or password. <a href=":url">Have you forgotten your password?</a>', [':url' => $password_url]));
          return;
        }
        if (user_is_blocked($username)) {
          $form_state->setError($name_element, $this->t('The username %name has not been activated or is blocked.', ['%name' => $username]));
          return;
        }
        if (!$this->credentialsCheckFlood->isAllowedHost($this->clientIp)) {
          $form_state->setError($name_element, $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $password_url]));
          $this->credentialsCheckFlood->register($this->clientIp, $username);
          return;
        }
        elseif (!$this->credentialsCheckFlood->isAllowedAccount($this->clientIp, $username)) {
          $form_state->setError($name_element, $this->t('Too many failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $password_url]));
          $this->credentialsCheckFlood->register($this->clientIp, $username);
          return;
        }

        $uid = $this->userAuth->authenticate($username, $password);
        if (!$uid) {
          $this->credentialsCheckFlood->register($this->clientIp, $username);
          $form_state->setError($name_element, $this->t('Unrecognized username or password. <a href=":url">Have you forgotten your password?</a>', [':url' => $password_url]));
        }
        $form_state->set('logged_in_uid', $uid);
        break;

      case 'register':
        $email = $values['register']['mail'];
        $username = $this->configuration['email_only'] ? $values['register']['mail'] : $values['register']['name'];
        $password = trim($values['register']['password']);
        if (empty($email)) {
          $form_state->setError($pane_form['register']['mail'], $this->t('Email field is required.'));
          return;
        }
        if (empty($this->configuration['email_only']) && empty($username)) {
          $form_state->setError($pane_form['register']['name'], $this->t('Username field is required.'));
          return;
        }
        if (empty($password)) {
          $form_state->setError($pane_form['register']['password'], $this->t('Password field is required.'));
          return;
        }

        /** @var \Drupal\user\UserInterface $account */
        $account = $this->entityTypeManager->getStorage('user')->create([
          'mail' => $email,
          'name' => $username,
          'pass' => $password,
          'status' => TRUE,
        ]);

        $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
        $form_display->extractFormValues($account, $pane_form['register'], $form_state);
        $form_display->validateFormValues($account, $pane_form['register'], $form_state);

        // Manually flag violations of fields not handled by the form display.
        // This is necessary as entity form displays only flag violations for
        // fields contained in the display.
        // @see \Drupal\user\AccountForm::flagViolations
        $violations = $account->validate();
        foreach ($violations->getByFields(['name', 'pass', 'mail']) as $violation) {
          list($field_name) = explode('.', $violation->getPropertyPath(), 2);
          $form_state->setError($pane_form['register'][$field_name], $violation->getMessage());
        }

        if (!$form_state->hasAnyErrors()) {
          $account->save();
          $form_state->set('logged_in_uid', $account->id());
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    $trigger = !empty($triggering_element['#op']) ? $triggering_element['#op'] : 'continue';
    switch ($trigger) {
      case 'continue':
        break;

      case 'login':
      case 'register':
        $storage = $this->entityTypeManager->getStorage('user');
        /** @var \Drupal\user\UserInterface $account */
        $account = $storage->load($form_state->get('logged_in_uid'));
        user_login_finalize($account);
        $this->order->setCustomer($account);
        $this->credentialsCheckFlood->clearAccount($this->clientIp, $account->getAccountName());
        break;
    }

    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $this->checkoutFlow->getNextStepId($this->getStepId()),
    ]);
  }

  /**
   * Checks whether guests can register after checkout is complete.
   *
   * @return bool
   *   TRUE if guests can register after checkout is complete, FALSE otherwise.
   */
  protected function canRegisterAfterCheckout() {
    $completion_register_pane = $this->checkoutFlow->getPane('completion_register');
    return $completion_register_pane->getStepId() != '_disabled';
  }

}
