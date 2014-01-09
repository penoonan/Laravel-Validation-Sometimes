<?php
namespace Estimator\Service\Validation;

interface ValidableInterface {

  /**
   * Add data to validate
   *
   * @param array
   * @return \Estimator\Service\Validation\ValidableInterface
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

  /**
   * Provides access to an instantiated validator's "sometimes" method
   * @param $sometimes
   * @return void
   */
  public function sometimes($sometimes);
}