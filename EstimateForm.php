<?php

namespace Estimator\Service\Form\Estimate;


use Estimator\Service\Validation;
use Estimator\Service\Form\Estimate\MNZipCodes as MNZipCodes;
use Estimator\Repo\Building\BuildingInterface;
use Estimator\Repo\HeavyItem\HeavyItemInterface;

class EstimateForm {

    /**
     * Validator
     *
     * @var \Estimator\Service\Validation\ValidableInterface
     */
    public $validator;

    /**
     * @var \HeavyItem
     */
    protected $heavy_item;

    /**
     * @var \Building
     */
    protected $building;

    /**
     * Defaults
     *
     * @var stdClass Object of default values
     */

    protected $defaults;


    public function __construct(EstimateFormValidator $validator, HeavyItemInterface $heavy_item, BuildingInterface $building)
    {
        $this->heavy_item = $heavy_item;
        $this->building = $building;

        $this->validator = $validator;
    }


    /**
     * Test if form validator passes
     * @param array $input
     * @return bool
     */
    public function valid(array $input)
    {
        return $this->validator->with($input)->passes();
    }


    /**
     * Return any validation errors
     *
     * @return array
     */

    public function errors()
    {
        return $this->validator->errors();
    }

    public function getDefaults()
    {
        $defaults = new \stdClass();
        $defaults->buildings = $this->getBuildings();
        $defaults->heavyItems = $this->getHeavyItems();
        $defaults->leadSource = array(
            'Angies List',
            'Bing',
            'Dexonline',
            'Facebook',
            'Google',
            'Magazine',
            'Mailers',
            'MSN',
            'Other',
            'Radio',
            'Realtor',
            'Referral',
            'Sales Rep',
            'Saw Truck',
            'Trade Show',
            'Yahoo',
            'Yelp'
        );

        return $defaults;
    }

    protected function getBuildings()
    {
        $buildings = array();
        $results = $this->building->all();

        foreach($results as $result)
        {
            $label = $this->building->getBuildingTypeById($result->building_type_id)->label;
            $buildings[$result->id] = $label;
        }
        //$buildings[count($buildings) + 1] = 'House - 4+ BDR';

        return $buildings ?: [];
    }

    protected function getHeavyItems()
    {
        $results = $this->heavy_item->all();

        foreach ($results as $result)
        {
            $heavy_items[] = array(
                'name' => $result->label
            );
        }

        return $heavy_items ?: [];
    }
}