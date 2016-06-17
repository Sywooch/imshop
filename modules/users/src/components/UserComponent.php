<?php

namespace im\users\components;

use DateTime;
use im\users\clients\ClientInterface;
use im\users\models\Auth;
use im\users\models\Profile;
use im\users\models\ProfileForm;
use im\users\models\Token;
use im\users\traits\ModuleTrait;
use im\users\models\RegistrationForm;
use im\users\models\User;
use Yii;
use yii\di\Instance;
use yii\web\IdentityInterface;

/**
 * Extended class for the "user" application component.
 *
 * @package im\users\components
 */
class UserComponent extends \yii\web\User
{
    use ModuleTrait;

    /**
     * @var UserMailerInterface|array|string the mailer object or the application component ID of the mailer object.
     */
    public $mailer = 'im\users\components\UserMailer';

    /**
     * @event ModelEvent an event that is triggered before user registration.
     */
    const EVENT_BEFORE_REGISTRATION = 'beforeRegistration';

    /**
     * @event Event an event that is triggered after a user is registered.
     */
    const EVENT_AFTER_REGISTRATION = 'afterRegistration';

    /**
     * @event ModelEvent an event that is triggered before user creation.
     */
    const EVENT_BEFORE_CREATION = 'beforeCreation';

    /**
     * @event Event an event that is triggered after a user is created.
     */
    const EVENT_AFTER_CREATION = 'afterCreation';

    /**
     * @event ModelEvent an event that is triggered before user updating.
     */
    const EVENT_BEFORE_UPDATING = 'beforeUpdating';

    /**
     * @event Event an event that is triggered after a user is updating.
     */
    const EVENT_AFTER_UPDATING = 'afterUpdating';

    /**
     * @event ModelEvent an event that is triggered before user deleting.
     */
    const EVENT_BEFORE_DELETING = 'beforeDeleting';

    /**
     * @event Event an event that is triggered after a user is deleting.
     */
    const EVENT_AFTER_DELETING = 'afterDeleting';

    /**
     * @event ModelEvent an event that is triggered before user confirmation.
     */
    const EVENT_BEFORE_CONFIRMATION = 'beforeConfirmation';

    /**
     * @event Event an event that is triggered after a user is confirmed.
     */
    const EVENT_AFTER_CONFIRMATION = 'afterConfirmation';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->mailer = Instance::ensure($this->mailer, 'im\users\components\UserMailerInterface');
    }

    /**
     * Registers user.
     *
     * @param \im\users\models\RegistrationForm $form
     * @return User|null the saved user or null if saving fails
     */
    public function register(RegistrationForm $form)
    {
        /** @var User $userClass */
        $userClass = $this->module->userModel;
        /** @var User $user */
        $user = Yii::createObject(['class' => $userClass, 'scenario' => $userClass::SCENARIO_REGISTER]);
        /** @var Profile $profileClass */
        $profileClass = $this->module->profileModel;
        /** @var Profile $profile */
        $profile = Yii::createObject(['class' => $profileClass, 'scenario' => $profileClass::SCENARIO_REGISTER]);

        $user->setAttributes([
            'username' => $form->username,
            'email' => $form->email,
            'password' => $form->password,
            'registration_ip' => ip2long(Yii::$app->request->userIP)
        ]);

        $profile->setAttributes([
            'first_name' => $form->firstName,
            'last_name' => $form->lastName
        ]);

        $user->profile = $profile;

        if (!$this->beforeRegister($user)) {
            return null;
        }
        if ($user->save() && $user->profile->save()) {
            $user->link('profile', $user->profile);
            if ($this->module->registrationConfirmation) {
                $this->sendConfirmationToken($user);
            }
            $this->afterRegister($user);
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Creates user.
     *
     * @param array $data
     * @return User|null the saved user or null if saving fails
     */
    public function create(array $data)
    {
        /** @var User $userClass */
        $userClass = $this->module->backendUserModel;
        /** @var User $user */
        $user = Yii::createObject(['class' => $userClass, 'scenario' => $userClass::SCENARIO_CREATE]);
        /** @var Profile $profileClass */
        $profileClass = $this->module->profileModel;
        /** @var Profile $profile */
        $profile = Yii::createObject(['class' => $profileClass, 'scenario' => $profileClass::SCENARIO_CREATE]);

        if ($user->load($data) && $profile->load($data)) {
            $user->profile = $profile;
            if ($user->status == $user::STATUS_BLOCKED && $user->getOldAttribute('status') != $user::STATUS_BLOCKED) {
                $user->block();
            }
            $confirm = $user->status == $user::STATUS_ACTIVE && $user->getOldAttribute('status') == $user::STATUS_NOT_CONFIRMED;
            if (!$this->beforeCreate($user)) {
                return null;
            }
            if ($user->save() && $user->profile->save()) {
                $user->link('profile', $user->profile);
                $this->afterCreate($user);
                if ($confirm) {
                    $this->confirm($user);
                }
                return $user;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Updates user.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function update(User $user, array $data)
    {
        $user->scenario = $user::SCENARIO_UPDATE;
        $profile = $user->profile;
        $user->profile->scenario = $profile::SCENARIO_UPDATE;

        if ($user->load($data) && $user->profile->load($data)) {
            if ($user->status == $user::STATUS_BLOCKED && $user->getOldAttribute('status') != $user::STATUS_BLOCKED) {
                $user->block();
            }
            $confirm = $user->status == $user::STATUS_ACTIVE && $user->getOldAttribute('status') == $user::STATUS_NOT_CONFIRMED;
            if (!$this->beforeUpdate($user)) {
                return false;
            }
            if ($user->save() && $user->profile->save()) {
                $this->afterUpdate($user);
                if ($confirm) {
                    $this->confirm($user);
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Delete user.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user)
    {
        if (!$this->beforeDelete($user)) {
            return false;
        }
        $user->delete();

        return true;
    }

    /**
     * Confirms a user.
     *
     * @param string $token
     * @throws TokenException
     * @return User|null the confirmed user or null if confirmation fails
     */
    public function confirmByToken($token)
    {
        /** @var Token $tokenClass */
        $tokenClass = $this->module->tokenModel;
        /** @var Token $token */
        $token = $tokenClass::findByToken($token, $tokenClass::TYPE_REGISTRATION_CONFIRMATION);

        if (!$token) {
            throw new TokenException('Token is not found.');
        }

        /** @var User $userClass */
        $userClass = $this->module->userModel;
        /** @var User $user */
        $user = $userClass::findOne($token->user_id);

        if ($confirmed = $this->confirm($user)) {
            $token->delete();
        }

        return $confirmed;
    }

    /**
     * Confirms user.
     *
     * @param User $user
     * @return User|null the confirmed user or null if confirmation fails
     */
    public function confirm(User $user)
    {
        if (!$this->beforeConfirm($user)) {
            return null;
        }
        $user->confirm();
        if ($user->save()) {
            $this->afterConfirm($user);
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Updates user profile.
     *
     * @param \im\users\models\ProfileForm $form
     * @return bool
     */
    public function updateProfile(ProfileForm $form)
    {
        if ($form->validate()) {
            $user = $form->getUser();
            $profile = $user->profile;
            $user->scenario = $user::SCENARIO_PROFILE;
            $user->username = $form->username;
            $user->email = $form->email;
            $user->password = $form->password;
            $user->profile->first_name = $form->firstName;
            $user->profile->last_name = $form->lastName;
            if (!$this->beforeUpdate($user)) {
                return false;
            }
            if ($user->save($user->profile) && $profile->save()) {
                $this->afterUpdate($user);
                return true;
            }
        }

        return false;
    }

    /**
     * Creates new confirmation token and sends it to the user.
     *
     * @param User $user user object
     * @return bool whether the confirmation information was resent.
     */
    public function sendConfirmationToken(User $user)
    {
        /** @var Token $tokenClass */
        $tokenClass = $this->module->tokenModel;
        $token = $tokenClass::generate($user->getId(), $tokenClass::TYPE_REGISTRATION_CONFIRMATION);

        return $this->mailer->sendRegistrationConfirmationEmail($user, $token);
    }

    /**
     * Return password recovery token by it's value.
     *
     * @param string $token
     * @return null|Token
     */
    public function getPasswordRecoveryToken($token)
    {
        /** @var Token $tokenClass */
        $tokenClass = $this->module->tokenModel;

        return $tokenClass::findByToken($token, $tokenClass::TYPE_PASSWORD_RECOVERY);
    }


    /**
     * Resets user's password.
     *
     * @param User $user user object
     * @param string $password new password
     * @return bool whether the password was changed.
     */
    public function resetPassword(User $user, $password)
    {
        $user->setPassword($password);

        return $user->save(false);
    }

    /**
     * @inheritdoc
     */
    public function login(IdentityInterface $identity, $duration = 0)
    {
        if (parent::login($identity, $duration)) {
            /** @var User $identity */
            $identity->last_login_ip = ip2long(Yii::$app->request->userIP);
            $identity->last_login_at = time();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates new password recovery token and sends it to the user.
     *
     * @param User $user user object
     * @return bool whether the recovery information was sent.
     */
    public function sendRecoveryToken(User $user)
    {
        /** @var Token $tokenClass */
        $tokenClass = $this->module->tokenModel;
        $expireTime = $this->module->passwordRecoveryTokenExpiration;
        $expireTime = $expireTime !== null ? (new DateTime())->modify('+' . $expireTime) : null;
        $token = $tokenClass::generate($user->getId(), $tokenClass::TYPE_PASSWORD_RECOVERY, $expireTime);

        return $this->mailer->sendPasswordRecoveryEmail($user, $token);
    }

    /**
     * Logs in user via social network using auth client.
     *
     * @param ClientInterface $client auth client
     * @return boolean whether the user is logged in
     */
    public function loginAuthClient(ClientInterface $client)
    {
        /** @var Auth $authClass */
        $authClass = $this->module->authModel;
        /** @var User $userClass */
        $userClass = $this->module->userModel;
        $attributes = $client->getUserAttributes();
        /** @var Auth $auth */
        $auth = $authClass::findByClient($client);

        if ($auth) {
            $user = $auth->user;
            $this->login($user);
            return true;
        } else {
            if (isset($attributes['email'])/* && isset($attributes['username'])*/) {
                /** @var User $user */
                $user = $userClass::findByEmail($attributes['email']);
                if ($user) {
                    $auth = $authClass::getInstance($client);
                    $auth->user_id = $user->getId();
                    $auth->save();
                    $this->login($user);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Registers a user from auth client.
     *
     * @param ClientInterface $client auth client
     * @return User|null the registered user or null if saving fails
     */
    public function registerAuthClient(ClientInterface $client)
    {
        /** @var User $userClass */
        $userClass = $this->module->userModel;
        /** @var User $user */
        $user = Yii::createObject(['class' => $userClass, 'scenario' => $userClass::SCENARIO_CONNECT]);
        /** @var Profile $profileClass */
        $profileClass = $this->module->profileModel;
        /** @var Profile $profile */
        $profile = Yii::createObject(['class' => $profileClass, 'scenario' => $profileClass::SCENARIO_CONNECT]);

        $user->setAttributes([
            'username' => $client->getUsername(),
            'email' => $client->getEmail(),
            'registration_ip' => ip2long(Yii::$app->request->userIP),
            'status' => $userClass::STATUS_ACTIVE
        ]);

        $profile->setFullName($client->getFullName());

        $transaction = $user->getDb()->beginTransaction();
        if ($user->save()) {
            $profile->user_id = $user->getId();
            /** @var Auth $authClass */
            $authClass = $this->module->authModel;
            $auth = $authClass::getInstance($client);
            $auth->user_id = $user->getId();
            if ($profile->save() && $auth->save()) {
                $transaction->commit();
                return $user;
            } else {
                print_r($profile->getErrors());
                print_r($auth->getErrors());
            }
        } else {
            print_r($user->getErrors());
        }

        return null;
    }

    /**
     * Connects auth client with user.
     *
     * @param ClientInterface $client auth client
     * @param IdentityInterface $identity the user identity
     * @return boolean whether the auth client is connected to user
     */
    public function connectAuthClient(ClientInterface $client, IdentityInterface $identity)
    {
        /** @var Auth $authClass */
        $authClass = $this->module->authModel;
        /** @var Auth $auth */
        $auth = $authClass::findByClient($client);
        if (!$auth) {
            $auth = $authClass::getInstance($client);
        }
        if (!$auth->user || $auth->isNewRecord) {
            $auth->user_id = $identity->getId();
            $auth->save();
        }

        return $auth->user_id ? true : false;
    }

    /**
     * This method is called at the beginning of user registration.
     * The default implementation will trigger an [[EVENT_BEFORE_REGISTRATION]] event.
     *
     * @param User $user user object
     * @return bool whether the registration should continue.
     */
    protected function beforeRegister(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_BEFORE_REGISTRATION, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of user registration.
     * The default implementation will trigger an [[EVENT_AFTER_REGISTRATION]] event.
     *
     * @param User $user
     */
    protected function afterRegister(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_AFTER_REGISTRATION, $event);
    }

    /**
     * This method is called at the beginning of user creation.
     * The default implementation will trigger an [[EVENT_BEFORE_CREATION]] event.
     *
     * @param User $user user object
     * @return bool whether the creation should continue.
     */
    protected function beforeCreate(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_BEFORE_CREATION, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of user creation.
     * The default implementation will trigger an [[EVENT_AFTER_CREATION]] event.
     *
     * @param User $user
     */
    protected function afterCreate(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_AFTER_CREATION, $event);
    }

    /**
     * This method is called at the beginning of user updating.
     * The default implementation will trigger an [[EVENT_BEFORE_UPDATING]] event.
     *
     * @param User $user user object
     * @return bool whether the updating should continue.
     */
    protected function beforeUpdate(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_BEFORE_UPDATING, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of user updating.
     * The default implementation will trigger an [[EVENT_AFTER_UPDATING]] event.
     *
     * @param User $user
     */
    protected function afterUpdate(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_AFTER_UPDATING, $event);
    }

    /**
     * This method is called at the beginning of user deleting.
     * The default implementation will trigger an [[EVENT_BEFORE_DELETING]] event.
     *
     * @param User $user user object
     * @return bool whether the deleting should continue.
     */
    protected function beforeDelete(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_BEFORE_DELETING, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of user deleting.
     * The default implementation will trigger an [[EVENT_AFTER_DELETING]] event.
     *
     * @param User $user
     */
    protected function afterDelete(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_AFTER_DELETING, $event);
    }

    /**
     * This method is called at the beginning of user confirmation.
     * The default implementation will trigger an [[EVENT_BEFORE_CONFIRMATION]] event.
     *
     * @param User $user user object
     * @return boolean whether the confirmation should continue.
     */
    protected function beforeConfirm(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_BEFORE_CONFIRMATION, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of user confirmation.
     * The default implementation will trigger an [[EVENT_AFTER_CONFIRMATION]] event.
     *
     * @param User $user
     */
    protected function afterConfirm(User $user)
    {
        $event = new UserEvent(['user' => $user]);
        $this->trigger(self::EVENT_AFTER_CONFIRMATION, $event);
    }
}