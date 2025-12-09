<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\CatalogueResource;
use App\Entity\Resource;
use App\Repository\RuleResourceRepository;

class RuleService
{
    public function __construct(private RuleResourceRepository $ruleResourceRepository)
    {
    }

    public function checkResourceRules(Booking $booking, Resource $resource): Resource|array
    {
        $rules = $this->ruleResourceRepository->findBy(['Resource' => $resource]);
        $checkRules = true;
        foreach ($rules as $rule) {
            $functionName = $rule->getRule()->getMethod();
            $ruleArguments = $rule->getRule()->getArguments();
            $userArguments = $rule->getArguments();
            $checkRules = RuleService::$functionName($ruleArguments, $userArguments, $booking, $resource);
            if($checkRules === false) {
                $rule = $rule->getRule()->getDescription().' ('. $this->returnOperand($rule->getArguments()['operand']). ' ' .$this->getRuleValueForUser($rule->getRule()->getArguments(), $rule->getArguments(), $resource).')';
                return ['rule' => [$rule => $resource->getTitle()]];
            }
        }
        if($checkRules) {
            return $resource;
        }
        return false;
    }

    static function capacityCheckWithUsers($ruleArguments, $userArguments, Booking $booking, Resource $resource): bool
    {
        $numberOfUser = sizeof($booking->getUser());
        if($ruleArguments["value"]['type'] === 'percentage') {
            $operand = $userArguments['operand'];
            $value = $userArguments['value'];
            $customField = self::getCustomFieldFromResource($resource, 'capacity');
            return self::compareValueWithOperand($numberOfUser, $customField*($value/100), $operand);
        }
        return false;
    }

    private static function compareValueWithOperand($value1, $value2, $operand): bool
    {
        $compare = false;
        switch ($operand) {
            case 'inferior':
                $compare = $value1 <= $value2;
                break;
            case 'superior':
                $compare = $value1 >= $value2;
                break;
            case 'equal':
                $compare = $value1 === $value2;
                break;
        }
        return $compare;
    }

    private static function getCustomFieldFromResource(Resource $resource, $fieldTitle): ?string
    {
        $fieldValue = null;
        foreach ($resource->getCustomFieldResources()->getValues() as $customField) {
            if($customField->getCustomField()->getTitle() === $fieldTitle) {
                $fieldValue = $customField->getValue();
            }
        }
        return $fieldValue;
    }

    public function getRulesDescriptions(Resource $resource): array
    {
        $rules = $this->ruleResourceRepository->findBy(['Resource' => $resource]);
        $descriptions = [];
        foreach ($rules as $rule) {
            $descriptions[] = $rule->getRule()->getDescription();
        }
        return $descriptions;
    }

    public function getCatalogRules(CatalogueResource $CatalogueResource)
    {
        $resources = $CatalogueResource->getResource()->getValues();
        $catalogRules = [];
        foreach ($resources as $resource) {
            $resourceTitle = $resource->getTitle();
            $rules = $this->ruleResourceRepository->findBy(['Resource' => $resource]);
            foreach ($rules as $rule) {
                $ruleResourceArguments = $rule->getArguments();
                $ruleArguments = $rule->getRule()->getArguments();
                $customFieldValue = $this->getRuleValueForUser($ruleArguments, $ruleResourceArguments, $resource);
                $ruleTitle = $rule->getRule()->getTitle();
                $ruleId = $rule->getRule()->getId();
                if(!isset($catalogRules[$ruleId])) {
                    $catalogRules[$ruleId] = '<b>'.$ruleTitle.'</b>
                    <ul><li>'.$resourceTitle.' : '.$this->returnOperand($ruleResourceArguments['operand']) . $customFieldValue.'</li>';
                } else {
                    $catalogRules[$ruleId].= '<li>'.$resourceTitle.' : '.$this->returnOperand($ruleResourceArguments['operand']) . $customFieldValue.'</li>';
                }
            }
        }
        foreach ($catalogRules as $key => $catalogRule) {
            $catalogRules[$key] .= '</ul>';
        }
        return $catalogRules;
    }

    private function returnOperand($operand)
    {
        $correctOperand = null;
        switch ($operand) {
            case 'inferior':
                $correctOperand = 'inférieur ou égal à ';
                break;
            case 'superior':
                $correctOperand = 'supérieur ou égal à ';
                break;
            case 'equal':
                $correctOperand = 'égal à';
                break;
        }
        return $correctOperand;
    }

    private function getRuleValueForUser($ruleArgument, $ruleResourceArgument, $resource)
    {
        $value = 0;

        if($ruleArgument["value"]['type'] === 'percentage') {
            $customField = self::getCustomFieldFromResource($resource, 'capacity');
            $value = ($ruleResourceArgument["value"]/100)*$customField;
        }
        return $value;
    }
}