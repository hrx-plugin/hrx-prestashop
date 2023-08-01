<?php

namespace Mijora\Hrx\DVDoug\BoxPacker;

class ParcelBox implements Box
{
    const DIMENSION_LENGTH = 'l';
    const DIMENSION_WIDTH = 'w';
    const DIMENSION_HEIGHT = 'h';
    const DIMENSION_WEIGHT = 'weight';

    const UNLIMITED = 9999; // used for box size checking instead of 0 which means unlimited in API data

    private $length; // mm
    private $width; // mm
    private $height; // mm
    private $weight; // g
    private $reference;

    public function __construct($length_cm, $width_cm, $height_cm, $max_weight_kg, $reference)
    {
        $this->length = $length_cm * 10;
        $this->width = $width_cm * 10;
        $this->height = $height_cm * 10;
        $this->weight = $max_weight_kg * 1000;
        $this->reference = $reference;
    }

    public function getEmptyWeight(): int
    {
        return 0;
    }

    public function getMaxWeight(): int
    {
        return (int) $this->weight;
    }

    public function getInnerLength(): int
    {
        return (int) $this->length;
    }

    public function  getOuterLength(): int
    {
        return $this->getInnerLength();
    }

    public function getInnerWidth(): int
    {
        return (int) $this->width;
    }

    public function getOuterWidth(): int
    {
        return $this->getInnerWidth();
    }

    public function getInnerDepth(): int
    {
        return (int) $this->height;
    }

    public function getOuterDepth(): int
    {
        return $this->getInnerDepth();
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}