<?php

namespace maranqz\StockBundle\Exception;

use Exception;
use Symfony\Component\Form\FormInterface;
use Throwable;

class StockError extends Exception
{
    /**
     * @var FormInterface
     */
    private $form;

    public function __construct(FormInterface $form, $code = 0, Throwable $previous = null)
    {
        $this->form = $form;

        parent::__construct($form->getErrors(), $code, $previous);
    }

    public function getFrom(): FormInterface
    {
        return $this->form;
    }
}
