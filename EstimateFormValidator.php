<?php

namespace Estimator\Service\Form\Estimate;

use AbstractValidator;

class EstimateFormValidator extends AbstractValidator {

  /**
   * @var \Illuminate\Validation\Factory
   */
  public $validator;

  /**
   * Validation rules
   *
   * @var Array
   */
  protected $rules = array(
    'first_name' => 'required',
  );

  /**
   * Validation messages
   *
   * @var Array
   */
  protected $messages = array(
    'first_name.required' => 'We need your first name! And if you give us your last name, we\'ll need your middle name too! Don\'t ask why!',
  );

  /**
   * Conditional rules that are only used "sometimes",
   * but which must be invoked *after* the concrete
   * validator has been instantiated
   * @var array
   */
  protected $sometimes = array(
    array(
      'field' => 'middle_name',
      'rule' => 'required',
      'callback' => 'checkMiddleNameIsRequired'
    ),
  );

  public function __construct(Validator $validator)
  {
    $this->validator = $validator;


    parent::__construct($validator);
  }

  /**
   * The first name is required, and we only need the middle name if the last name was also given.
   * Yes, that is absurd.
   * @param $input
   * @return boolean
   */
  public function checkMiddleNameIsRequired($input)
  {
      return isset($input->last_name);
  }

}