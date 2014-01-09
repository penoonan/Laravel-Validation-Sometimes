<?php

namespace Estimator\Service\Form\Estimate;

use Estimator\Service\Validation\AbstractLaravelValidator;
use Illuminate\Validation\Factory as Validator;
use Estimator\Service\Form\Estimate\MNZipCodes;
use Estimator\Repo\Blackout\BlackoutInterface;

class EstimateFormValidator extends AbstractLaravelValidator {

  /**
   * @var array - DB query results
   */
  protected $blackout_date_objects;

  /**
   * stores whether or not user's selection is on a blackout date
   * so we don't have to run that calculation more than once
   * @var boolean
   */
  protected $is_blackout_date;

  /**
   * From user's input - so we can validate against it
   * @var string
   */
  protected $move_time_of_day;

  /**
   * Keys are MySQL date timestamp,
   * values are "meridian" field
   * @var array
   */
  protected $blackout_times;

  /**
   * Validation rules
   *
   * @var Array
   */
  protected $rules = array(
    'from_address'            =>  'required',
    'from_city'               =>  'required',
    'from_state'              =>  'required|alpha|size:2',
    'from_zip'                =>  'required|min:5|regex:[^\d+$]|is_mn_zip',
    'from_type'               =>  'required|integer|not_in:9',
    'from_has_elevator'       =>  'required|in:0,1',
    'from_flights_of_stairs'  =>  'required|integer|between:0,3',
    'from_truck_distance'     =>  'required|in:25,50,100',
    'to_address'              =>  'required',
    'to_city'                 =>  'required',
    'to_state'                =>  'required|size:2',
    'to_zip'                  =>  'required|min:5|regex:[^\d+$]|is_mn_zip',
    'to_type'                 =>  'required|integer|not_in:9',
    'to_has_elevator'         =>  'required|in:0,1',
    'to_flights_of_stairs'    =>  'required|integer|between:0,3',
    'to_truck_distance'       =>  'required|in:25,50,100',
    'move_date'               =>  'required|date:mm/dd/yyyy|after:now|is_not_blackout_date',
    'move_time_of_day'        =>  'required|in:morning,afternoon,no_preference',
    'need_storage'            =>  'required|in:0,1' ,
    'months_of_storage'       =>  'integer|between:0,100',
    'need_packing'            =>  'required|in:0,1',
    'has_special_items'       =>  'required|in:0,1',
    'contact_name'            =>  'required',
    'contact_email'           =>  'required|email',
    'contact_tel'             =>  'required|regex:[^[0-9-.,]*$]'
  );

  /**
   * Validation messages
   *
   * @var Array
   */
  protected $messages = array(
    'from_zip.regex'                        => 'Invalid "from" zip code',
    'to_zip.regex'                          => 'Invalid "to" zip code',
    'contact_tel.regex'                     => 'Invalid phone number',
    'move_date.after'                       => 'The move date can not be in the past',
    'from_zip.is_mn_zip'                    => '"From" zip code is not in Minnesota',
    'to_zip.is_mn_zip'                      => '"To" zip code is not in Minnesota',
    'from_type.not_in'                      => 'Your house is too big for our online estimator! Please call 1-866-490-MOVE to talk with a AAA moving specialist',
    'to_type.not_in'                        => 'Your house is too big for our online estimator! Please call 1-866-490-MOVE to talk with a AAA moving specialist',
    'move_date.is_not_blackout_date'        => 'Sorry, we are all booked up on the date you chose! Please try another date',
    'move_date.is_not_blackout_morning'     => 'Sorry, we are all booked up on the morning of the date you chose! Please select that afternoon or try a different date',
    'move_date.is_not_blackout_afternoon'   => 'Sorry, we are all booked up on the afternoon of the date you chose! Please select that morning or try a different date'
  );

  /**
   * Conditional rules that are only used "sometimes",
   * but which must be invoked *after* the concrete
   * validator has been instantiated
   * @var array
   */
  protected $sometimes = array(
    array(
      'field' => 'move_date',
      'rule' => 'is_not_blackout_morning',
      'callback' => 'checkBlackoutMorning'
    ),
    array(
      'field' => 'move_date',
      'rule' => 'is_not_blackout_afternoon',
      'callback' => 'checkBlackoutAfternoon'
    )
  );

  /**
   * Valid MN Zip Codes
   * @var
   */
  public $zip_codes;

  /**
   * @var \Estimator\Repo\Blackout\BlackoutInterface
   */
  protected $blackout;

  /**
   * @var \Illuminate\Validation\Factory
   */
  public $validator;

  public function __construct(Validator $validator, MNZipCodes $zip_codes, BlackoutInterface $blackout)
  {
    $this->zip_codes = $zip_codes->getZipCodes();
    $this->validator = $validator;
    $this->blackout = $blackout;

    $this->validator->extend(
      'is_mn_zip',
      function($attribute, $value, $parameters)
      {
        $result = in_array($value, $this->zip_codes);
        return $result;
      }
    );

    $this->validator->extend(
      'is_not_blackout_date',
      function($attribute, $value, $parameters)
      {
        $result = $this->validateIsNotBlackoutDate($value);
        return $result;
      }
    );

    $this->validator->extend(
      'is_not_blackout_morning',
      function($attribute, $value, $parameters)
      {
        $result = $this->validateIsNotBlackoutMorning($value);
        return $result;
      }
    );

    $this->validator->extend(
      'is_not_blackout_afternoon',
      function($attribute, $value, $parameters)
      {
        $result = $this->validateIsNotBlackoutAfternoon($value);
        return $result;
      }
    );

    parent::__construct($validator);
  }

  public function validateIsMnZip($value)
  {
    return !in_array($value, $this->zip_codes);
  }


  /**
   * Provides a single access point for the various
   * blackout date/time validators
   * @param $date
   * @param $time
   * @return bool
   */
  protected function validateIsNotBlackoutTime($date, $time)
  {
    $blackout_times = $this->getBlackoutTimes();
    $date = date('Y-m-d h:i:s', strtotime($date));
    $blackout_time_of_day = isset($blackout_times[$date]) ? $blackout_times[$date] : false;

    return !in_array($blackout_time_of_day, array($time, 'all'));
  }


  public function validateIsNotBlackoutDate($attribute)
  {
    return $this->validateIsNotBlackoutTime($attribute, 'all');
  }

  public function validateIsNotBlackoutMorning($attribute)
  {
    return $this->validateIsNotBlackoutTime($attribute, 'am');
  }

  public function validateIsNotBlackoutAfternoon($attribute)
  {
    return $this->validateIsNotBlackoutTime($attribute, 'pm');
  }


  public function checkBlackoutDate($input)
  {
    return $input->move_time_of_day === 'no_preference';
  }

  public function checkBlackoutMorning($input)
  {
    return $input->move_time_of_day === 'morning';
  }

  public function checkBlackoutAfternoon($input)
  {
    return $input->move_time_of_day === 'afternoon';
  }


  protected function getBlackoutTimes()
  {
    if (isset($this->blackout_times)) return $this->blackout_times;
    $blackout_date_objects = $this->getBlackoutDates();

    foreach($blackout_date_objects as $blackout_date_object) {
      $blackout_times[$blackout_date_object->blackout_date] = $blackout_date_object->meridian;
    }

    $this->blackout_times = isset($blackout_times) ? $blackout_times : array();
    return $this->blackout_times;
  }

  protected function getBlackoutDates()
  {
    if (isset($this->blackout_date_objects)) return $this->blackout_date_objects;

    $this->blackout_date_objects = $this->blackout->getDates();

    return $this->blackout_date_objects;
  }



}