<?php

namespace Estimator\Service\Form;

use Estimator\Service\Form\EstimatorSettings\EstimatorSettingsForm;
use Illuminate\Support\ServiceProvider;
use Estimator\Service\Form\Estimate\EstimateForm;
use Estimator\Service\Form\Estimate\EstimateFormValidator;
use Estimator\Service\Form\Estimate\MNZipCodes as MNZipCodes;

use Estimator\Service\Form\EstimatorSettings\EstimatorSettingsFormValidator;


class FormServiceProvider extends ServiceProvider {

    /**
     * Register the binding
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        $app->bind('Estimator\Service\Form\Estimate\EstimateForm', function($app)
        {
            return new EstimateForm(
                new EstimateFormValidator(
                    $app['validator'],
                    new MNZipCodes(),
                    $app->make('Estimator\Repo\Blackout\BlackoutInterface')
                ),
                $app->make('Estimator\Repo\HeavyItem\HeavyItemInterface'),
                $app->make('Estimator\Repo\Building\BuildingInterface')
            );
        });

        $app->bind('Estimator\Service\Form\EstimatorSettings\EstimatorSettingsForm', function($app)
        {
            return new EstimatorSettingsForm(
                new EstimatorSettingsFormValidator($app['validator']),
                $app->make('Estimator\Repo\Building\BuildingInterface'),
                $app->make('Estimator\Repo\Crew\CrewInterface'),
                $app->make('Estimator\Repo\HeavyItem\HeavyItemInterface'),
                $app->make('Estimator\Repo\HourModifier\HourModifierInterface'),
                $app->make('Estimator\Repo\MovingMeta\MovingMetaInterface'),
                $app->make('Estimator\Repo\StairModifier\StairModifierInterface')
            );
        });
    }

}