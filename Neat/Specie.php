<?php

namespace Gheb\NeatBundle\Neat;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Species
 *
 * @author  Grégoire Hébert <gregoire@opo.fr>
 */
class Specie
{
    /**
     * @var int
     */
    public $averageFitness = 0;

    /**
     * @var ArrayCollection
     */
    public $genomes;

    /**
     * @var int
     */
    public $id;

    /**
     * @var Pool
     */
    public $pool;

    /**
     * @var int
     */
    public $staleness = 0;

    /**
     * @var int
     */
    public $topFitness = 0;

    public function __construct()
    {
        $this->genomes = new ArrayCollection();
    }

    public function addGenome(Genome $genome)
    {
        $this->genomes->add($genome);
        $genome->setSpecie($this);
    }

    /**
     * Calculate the average fitness based on genome global rank
     */
    public function calculateAverageFitness()
    {
        $total = 0;
        /** @var Genome $genome */
        foreach ($this->genomes as $genome) {
            $total += $genome->getGlobalRank();
        }

        $this->setAverageFitness($total / $this->genomes->count());
    }

    /**
     * @return int
     */
    public function getAverageFitness()
    {
        return $this->averageFitness;
    }

    /**
     * @return ArrayCollection
     */
    public function getGenomes()
    {
        return $this->genomes;
    }

    public function getBestGenome() {
        /** var Genome $genome */
        return $this->getGenomes()->filter(function($genome){
            /** var Genome $genome */
            return $genome->getFitness() == $this->getTopFitness();
        })->first();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @return int
     */
    public function getStaleness()
    {
        return $this->staleness;
    }

    /**
     * @return int
     */
    public function getTopFitness()
    {
        return $this->topFitness;
    }

    public function removeGenome(Genome $genome)
    {
        $genome->setSpecie(null);
        $this->genomes->removeElement($genome);
    }

    /**
     * @param int $averageFitness
     */
    public function setAverageFitness($averageFitness)
    {
        $this->averageFitness = $averageFitness;
    }

    /**
     * @param ArrayCollection $genomes
     */
    public function setGenomes($genomes)
    {
        $this->genomes = $genomes;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param Pool $pool
     */
    public function setPool($pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param int $staleness
     */
    public function setStaleness($staleness)
    {
        $this->staleness = $staleness;
    }

    /**
     * @param int $topFitness
     */
    public function setTopFitness($topFitness)
    {
        $this->topFitness = $topFitness;
    }
}