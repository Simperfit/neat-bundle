<?php

namespace Gheb\NeatBundle\Manager;

use Doctrine\ORM\EntityManager;
use Gheb\IOBundle\Inputs\InputsAggregator;
use Gheb\IOBundle\Outputs\AbstractOutput;
use Gheb\IOBundle\Outputs\OutputsAggregator;
use Gheb\IOBundle\Aggregator;
use Gheb\NeatBundle\Neat\Genome;
use Gheb\NeatBundle\Neat\Mutation;
use Gheb\NeatBundle\Neat\Pool;
use Gheb\NeatBundle\Neat\Specie;
use Gheb\NeatBundle\Neat\Network;

class Manager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var InputsAggregator
     */
    private $inputsAggregator;

    /**
     * @var Mutation
     */
    private $mutation;

    /**
     * @var OutputsAggregator
     */
    private $outputsAggregator;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * Manager constructor.
     *
     * @param EntityManager $em
     * @param Aggregator    $inputsAggregator
     * @param Aggregator    $outputsAggregator
     * @param Mutation      $mutation
     */
    public function __construct(
        EntityManager $em,
        Aggregator $inputsAggregator,
        Aggregator $outputsAggregator,
        Mutation $mutation
    ) {
        $this->em                = $em;
        $this->inputsAggregator  = $inputsAggregator;
        $this->outputsAggregator = $outputsAggregator;
        $this->mutation          = $mutation;

        $repo       = $this->em->getRepository('Gheb\NeatBundle\Entity\Pool');
        $this->pool = $repo->findOneBy([]);

        if (!$this->pool instanceof Pool) {
            $this->initializePool();
        } else {
            $this->pool->setEm($em);
            $this->pool->setInputAggregator($inputsAggregator);
            $this->pool->setMutation($mutation);
        }
    }

    public function applyOutputs($outputs)
    {
        /** @var AbstractOutput $output */
        foreach ($outputs as $output) {
            try {
                $output->apply();
            } catch (\Exception $e) {
                var_dump($e->getMessage());

                return;
            }
        }
    }

    public function evaluateCurrent()
    {
        /** @var Specie $specie */
        /* @var Genome $genome */
        $specie = $this->pool->getSpecies()->offsetGet($this->pool->getCurrentSpecies());
        $genome = $specie->getGenomes()->offsetGet($this->pool->getCurrentGenome());

        $inputs  = $this->inputsAggregator->aggregate->toArray();
        $outputs = Network::evaluate($genome, $inputs, $this->outputsAggregator, $this->inputsAggregator);

        $this->applyOutputs($outputs);
    }

    public function evaluateBest(){
        $genome = $this->pool->getBestGenome();

        $inputs  = $this->inputsAggregator->aggregate->toArray();
        $outputs = Network::evaluate($genome, $inputs, $this->outputsAggregator, $this->inputsAggregator);

        $this->applyOutputs($outputs);
    }

    /**
     * Return either a genome fitness has been measured or not
     *
     * @return bool
     */
    public function fitnessAlreadyMeasured()
    {
        /** @var Specie $specie */
        $specie = $this->pool->getSpecies()->offsetGet($this->pool->getCurrentSpecies());

        /** @var Genome $genome */
        $genome = $specie->getGenomes()->offsetGet($this->pool->getCurrentGenome());

        return $genome->getFitness() != 0;
    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @return InputsAggregator
     */
    public function getInputsAggregator()
    {
        return $this->inputsAggregator;
    }

    /**
     * @return Mutation
     */
    public function getMutation()
    {
        return $this->mutation;
    }

    /**
     * @return OutputsAggregator
     */
    public function getOutputsAggregator()
    {
        return $this->outputsAggregator;
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    public function initializePool()
    {
        $pool = new Pool($this->em, $this->outputsAggregator, $this->inputsAggregator, $this->mutation);
        $this->em->persist($pool);
        $this->em->flush();

        $repo       = $this->em->getRepository('Gheb\NeatBundle\Entity\Pool');
        $this->pool = $repo->findOneBy([]);

        for ($i = 0; $i < Pool::POPULATION; $i++) {
            $this->pool->addToSpecies($this->pool->createBasicGenome());
        }

        $this->initializeRun();
    }

    public function initializeRun()
    {
        /** @var Specie $specie */
        /* @var Genome $genome */
        $specie = $this->pool->getSpecies()->offsetGet($this->pool->getCurrentSpecies());
        $genome = $specie->getGenomes()->offsetGet($this->pool->getCurrentGenome());

        Network::generateNetwork($genome, $this->outputsAggregator, $this->inputsAggregator);

        $this->evaluateCurrent();
    }

    /**
     * @param EntityManager $em
     */
    public function setEm($em)
    {
        $this->em = $em;
    }

    /**
     * @param InputsAggregator $inputsAggregator
     */
    public function setInputsAggregator($inputsAggregator)
    {
        $this->inputsAggregator = $inputsAggregator;
    }

    /**
     * @param Mutation $mutation
     */
    public function setMutation($mutation)
    {
        $this->mutation = $mutation;
    }

    /**
     * @param OutputsAggregator $outputsAggregator
     */
    public function setOutputsAggregator($outputsAggregator)
    {
        $this->outputsAggregator = $outputsAggregator;
    }

    /**
     * @param Pool $pool
     */
    public function setPool($pool)
    {
        $this->pool = $pool;
    }
}
