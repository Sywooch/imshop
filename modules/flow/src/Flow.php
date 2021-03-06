<?php

namespace im\flow;

use im\flow\step\StepInterface;
use InvalidArgumentException;

/**
 * Class Flow
 * @package im\flow
 */
class Flow implements FlowInterface
{
    /**
     * Flow scenario alias.
     *
     * @var string
     */
    protected $scenarioAlias;

    /**
     * Steps.
     *
     * @var StepInterface[]
     */
    protected $steps = [];

    /**
     * Ordered steps.
     *
     * @var StepInterface[]
     */
    protected $orderedSteps = [];

    /**
     * @var FlowValidatorInterface
     */
    protected $validator;

    /**
     * Display action route.
     *
     * @var string
     */
    protected $displayRoute;

    /**
     * Display action route params.
     *
     * @var array
     */
    protected $displayRouteParams = [];

    /**
     * Forward action route.
     *
     * @var string
     */
    protected $forwardRoute;

    /**
     * Forward action route params.
     *
     * @var array
     */
    protected $forwardRouteParams = [];

    /**
     * Redirect route.
     *
     * @var string
     */
    protected $redirect;

    /**
     * Redirect route params.
     *
     * @var array
     */
    protected $redirectParams = [];

    /**
     * {@inheritdoc}
     */
    public function getScenarioAlias()
    {
        return $this->scenarioAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function setScenarioAlias($scenarioAlias)
    {
        $this->scenarioAlias = $scenarioAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * {@inheritdoc}
     */
    public function setSteps(array $steps)
    {
        foreach ($steps as $name => $step) {
            $this->addStep($name, $step);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderedSteps()
    {
        return $this->orderedSteps;
    }

    /**
     * {@inheritdoc}
     */
    public function getStepByIndex($index)
    {
        if (!isset($this->orderedSteps[$index])) {
            throw new InvalidArgumentException(sprintf('Step with index %d. does not exist', $index));
        }

        return $this->orderedSteps[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function getStepByName($name)
    {
        if (!$this->hasStep($name)) {
            throw new InvalidArgumentException(sprintf('Step with name "%s" does not exist', $name));
        }

        return $this->steps[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstStep()
    {
        return $this->getStepByIndex(0);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastStep()
    {
        return $this->getStepByIndex($this->countSteps() - 1);
    }

    /**
     * {@inheritdoc}
     */
    public function countSteps()
    {
        return count($this->steps);
    }

    /**
     * {@inheritdoc}
     */
    public function addStep($name, StepInterface $step)
    {
        if ($this->hasStep($name)) {
            throw new InvalidArgumentException(sprintf('Step with name "%s" already exists', $name));
        }

        if (null === $step->getName()) {
            $step->setName($name);
        }

        $this->steps[$name] = $this->orderedSteps[] = $step;
    }

    /**
     * {@inheritdoc}
     */
    public function removeStep($name)
    {
        if (!$this->hasStep($name)) {
            throw new InvalidArgumentException(sprintf('Step with name "%s" does not exist', $name));
        }

        $index = array_search($this->steps[$name], $this->orderedSteps);

        unset($this->steps[$name], $this->orderedSteps[$index]);
        $this->orderedSteps = array_values($this->orderedSteps); //keep sequential index intact
    }

    /**
     * {@inheritdoc}
     */
    public function hasStep($name)
    {
        return isset($this->steps[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayRoute()
    {
        return $this->displayRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayRoute($route)
    {
        $this->displayRoute = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayRouteParams()
    {
        return $this->displayRouteParams;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayRouteParams(array $params)
    {
        $this->displayRouteParams = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getForwardRoute()
    {
        return $this->forwardRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function setForwardRoute($route)
    {
        $this->forwardRoute = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getForwardRouteParams()
    {
        return $this->forwardRouteParams;
    }

    /**
     * {@inheritdoc}
     */
    public function setForwardRouteParams(array $params)
    {
        $this->forwardRouteParams = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectParams()
    {
        return $this->redirectParams;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectParams(array $params)
    {
        $this->redirectParams = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidator(FlowValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
}
