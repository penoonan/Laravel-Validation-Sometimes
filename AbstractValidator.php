<?php

use Illuminate\Validation\Factory;

abstract class AbstractValidator implements ValidatorInterface {

    /**
     * Validator
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Validation data key => value array
     *
     * @var Array
     */
    protected $data = array();

    /**
     * Validation errors
     *
     * @var Array
     */
    protected $errors = array();

    /**
     * Validation rules
     *
     * @var Array
     */
    protected $rules = array();

    /**
     * Custom validation messages
     *
     * @var Array
     */
    protected $messages = array();

    /**
     * @var
     */
    protected $sometimes = array();

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function with(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validation passes or fails
     *
     * @return Boolean
     */
    public function passes()
    {
        $validator = $this->validator->make(
            $this->data,
            $this->rules,
            $this->messages
        );

        foreach ($this->sometimes as $sometime) {
          $validator->sometimes($sometime['field'], $sometime['rule'], function($input) use ($sometime)
            {
              return $this->$sometime['callback']($input);
            });
        }

        if( $validator->fails())
        {
            $this->errors = $validator->messages();
            return false;
        }

        return true;
    }

    /**
     * Return errors, if any
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

}