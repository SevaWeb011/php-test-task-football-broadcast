<?php
namespace App\Entity;

class TypePlayer extends Player{
    
    private string $type;
    private const TYPE_GOALKEEPER = 'В';
    private const TYPE_DEFENDER = 'З';
    private const TYPE_MIDFIELDER = 'П';
    private const TYPE_ATTACK = 'Н';

    private const PLAYER_TYPES = [
        self::TYPE_GOALKEEPER,
        self::TYPE_DEFENDER,
        self::TYPE_MIDFIELDER,
        self::TYPE_ATTACK
    ];

    public function __construct(int $number, string $name, string $type)
    {
        parent::__construct($number, $name);
        $this->_setType($type);
    }

    public function getType():string
    {
        return $this->type;
    }

    private function _setType(string $type):void
    {
        foreach(self::PLAYER_TYPES as $playerType){
            if($playerType === $type){
                $this->type = $type;
            }    
        }
        if(empty($type))
            throw new \Exception('This type not exist');
    }
}
?>
