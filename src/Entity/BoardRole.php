<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRoleRepository")
 * @ORM\Table(name="board_rights")
 */
class BoardRole extends AbstractRoleEntity {
  /**
   * @ORM\ManyToOne(targetEntity="Board", cascade={"remove"})
   * @ORM\JoinColumn(name="id_board", referencedColumnName="id")
   */
  private $board;

  /**
   * @param Board $board
   */
  public function setBoard($board) {
    $this->board = $board;
  }

  /** @return Board */
  public function getBoard() {
    return $this->board;
  }
}