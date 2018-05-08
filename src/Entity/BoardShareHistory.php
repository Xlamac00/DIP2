<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="App\Repository\BoardShareHistoryRepository")
 * @ORM\Table(name="board_history")
 */
class BoardShareHistory extends AbstractHistoryRoleEntity {
  /**
   * @ORM\ManyToOne(targetEntity="Board", cascade={"remove"})
   * @ORM\JoinColumn(name="id_board", referencedColumnName="id")
   */
  private $entity;

  public function setBoard($board) {
    $this->entity = $board;
  }

  public function getBoard() {
    return $this->entity;
  }
}