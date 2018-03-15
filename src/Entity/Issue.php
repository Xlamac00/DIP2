<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IssueRepository")
 * @ORM\Table(name="issue")
 */
class Issue {
    public function __construct() {
      $this->gauges = new ArrayCollection();
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Board")
     * @ORM\JoinColumn(name="board_id", referencedColumnName="id")
     */
    private $board;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $link;

    /**
     * @ORM\OneToMany(targetEntity="Gauge", mappedBy="issue")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $gauges;

    /**
     * @return Collection|Gauge[]
     */
    public function getGauges()  {
      return $this->gauges;
    }

    public function getGaugesSorted() {
      return usort($this->gauges, array($this, "compare"));
    }

    /** Compares gauges to sort them by position */
    private function compare($a, $b) {
      return strcmp($a->getPosition(), $b->getPosition());
    }

    /**
     * @return mixed
     */
    public function getId() {
      return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName() {
      return $this->name;
    }

    public function setName($newName) {
      $this->name = $newName;
    }

    /**
     * @param Gauge $gauge
     */
    public function setGauge($gauge) {
      $this->gauges[] = $gauge;
    }

    /**
     * @return mixed
     */
    public function getLink() {
      return $this->link;
    }
}
