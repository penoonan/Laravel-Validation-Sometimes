<?php

interface ValidatorInterface {

  /**
   * Add data to validate
   *
   * @param array
   * @return ValidatorInterface
   */
  public function with(array $input);

  /**
   * Test if validation passes
   *
   * @return boolean
   */
  public function passes();

  /**
   * Retrieve validation errors
   *
   * @return array
   */
  public function errors();

}