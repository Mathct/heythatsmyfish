<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * heythatsmyfish implementation : © <Mathieu Chatrain> <mathieu.chatrain@gmail.com> && <Yannick Priol> <camertwo@hotmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\heythatsmyfish;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

include('Pending.php'); // ATTENTION

class Game extends \Table
{
    private static array $CARD_TYPES; // ATTENTION


    public static $instance = null; //ATTENTION

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        require 'material.inc.php';

        $this->initGameStateLabels([

            "endgame" => 10,
        ]);

        self::$instance = $this; // ATTENTION

        $this->tile = self::getNew("module.common.deck");
        $this->tile->init("tile");
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "heythatsmyfish";
    }


    /////////////////////////////////////////////////////////////////////////////////  
    //       _____                        _____       _ _   _       _ _          _   _             
    //      / ____|                      |_   _|     (_) | (_)     | (_)        | | (_)            
    //     | |  __  __ _ _ __ ___   ___    | |  _ __  _| |_ _  __ _| |_ ______ _| |_ _  ___  _ __  
    //     | | |_ |/ _` | '_ ` _ \ / _ \   | | | '_ \| | __| |/ _` | | |_  / _` | __| |/ _ \| '_ \ 
    //     | |__| | (_| | | | | | |  __/  _| |_| | | | | |_| | (_| | | |/ / (_| | |_| | (_) | | | |
    //      \_____|\__,_|_| |_| |_|\___| |_____|_| |_|_|\__|_|\__,_|_|_/___\__,_|\__|_|\___/|_| |_|
    //                                                                                               
    /////////////////////////////////////////////////////////////////////////////////    


    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        //$this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();


        self::initStat( 'table', 'turns_number', 0 );
        

        self::initStat( 'player', 'turns_number', 0 );
        self::initStat( 'player', 'fish_collected', 0 );
        self::initStat( 'player', 'tiles_collected', 0 );
        
        $this->setGameStateInitialValue("endgame", 0);


        // INIT ICE //

        $tile = array();
        $tile[] = array('type' => 1, 'type_arg' => 1, 'nbr' => 10);
        $tile[] = array('type' => 1, 'type_arg' => 2, 'nbr' => 10);
        $tile[] = array('type' => 1, 'type_arg' => 3, 'nbr' => 10);
        $tile[] = array('type' => 2, 'type_arg' => 1, 'nbr' => 10);
        $tile[] = array('type' => 2, 'type_arg' => 2, 'nbr' => 5);
        $tile[] = array('type' => 2, 'type_arg' => 3, 'nbr' => 5);
        $tile[] = array('type' => 3, 'type_arg' => 1, 'nbr' => 5);
        $tile[] = array('type' => 3, 'type_arg' => 2, 'nbr' => 3);
        $tile[] = array('type' => 3, 'type_arg' => 3, 'nbr' => 2);

        $this->tile->createCards($tile, 'deck');
        $this->tile->shuffle('deck');

        for ($i = 1; $i <= 7; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 11; $i <= 18; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 21; $i <= 27; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 31; $i <= 38; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 41; $i <= 47; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 51; $i <= 58; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 61; $i <= 67; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }

        for ($i = 71; $i <= 78; $i++) {
            $this->tile->pickCardForLocation('deck', 'board', $i);
        }




        $nbreplayers = count(self::getObjectListFromDB("SELECT player_id FROM player", true));

        foreach ($players as $player_id => $player) {
            if ($nbreplayers == 2) {
                for ($i = 1; $i <= 4; $i++) {
                    self::DbQuery("INSERT INTO penguin (player_id, no, hex) VALUES ($player_id, $i,0)");
                }
            }

            if ($nbreplayers == 3) {
                for ($i = 1; $i <= 3; $i++) {
                    self::DbQuery("INSERT INTO penguin (player_id, no, hex) VALUES ($player_id, $i,0)");
                }
            }

            if ($nbreplayers == 4) {
                for ($i = 1; $i <= 2; $i++) {
                    self::DbQuery("INSERT INTO penguin (player_id, no, hex) VALUES ($player_id, $i,0)");
                }
            }
        }




        /************ Init Pending *****/


        foreach ($players as $player_id => $player) {
            $this->addPendingFirst($player_id, "FirstTurn");
        }
    }

    /////////////////////////////////////////////////////////////////////////////////  
    //               _            _ _ _____        _            
    //              | |     /\   | | |  __ \      | |           
    //     __ _  ___| |_   /  \  | | | |  | | __ _| |_ __ _ ___ 
    //    / _` |/ _ \ __| / /\ \ | | | |  | |/ _` | __/ _` / __|
    //   | (_| |  __/ |_ / ____ \| | | |__| | (_| | || (_| \__ \
    //    \__, |\___|\__/_/    \_\_|_|_____/ \__,_|\__\__,_|___/
    //     __/ |                                                
    //    |___/                                                 
    /////////////////////////////////////////////////////////////////////////////////  

    protected function getAllDatas()
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $sql = "SELECT player_id id, player_score score, player_color color, player_no no, player_name name, player_last_tile last_tile FROM player ORDER BY player_no";
        $result['players'] = self::getCollectionFromDb($sql);

        $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg FROM tile WHERE 1";
        $result["tiles"] = self::getObjectListFromDb($sql);

        $sql = "SELECT id, player_id, no, hex FROM penguin WHERE 1";
        $result["penguins"] = self::getObjectListFromDb($sql);

        $sql_tiles = "SELECT 
                card_location AS player_id, 
                COUNT(card_id) AS tiles_collected
            FROM tile 
            WHERE card_location != 'board'
            GROUP BY card_location";
        $result["tiles_collected"] = self::getCollectionFromDB($sql_tiles, true);

        $sql_fish = "SELECT 
                card_location AS player_id, 
                SUM(card_type) AS fish_collected
            FROM tile 
            WHERE card_location != 'board'
            GROUP BY card_location";
        $result["fish_collected"] = self::getCollectionFromDB($sql_fish, true);


        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }


    /////////////////////////////////////////////////////////////////////////////////  
    //     _____                      _____                                   _             
    //    / ____|                    |  __ \                                 (_)            
    //   | |  __  __ _ _ __ ___   ___| |__) | __ ___   __ _ _ __ ___  ___ ___ _  ___  _ __  
    //   | | |_ |/ _` | '_ ` _ \ / _ \  ___/ '__/ _ \ / _` | '__/ _ \/ __/ __| |/ _ \| '_ \ 
    //   | |__| | (_| | | | | | |  __/ |   | | | (_) | (_| | | |  __/\__ \__ \ | (_) | | | |
    //    \_____|\__,_|_| |_| |_|\___|_|   |_|  \___/ \__, |_|  \___||___/___/_|\___/|_| |_|
    //                                                 __/ |                                
    //                                                |___/                                 
    /////////////////////////////////////////////////////////////////////////////////  

    public function getGameProgression()
    {
        $nbre_hex_restant = count(self::getObjectListFromDB( "SELECT card_id FROM tile WHERE card_location = 'board'", true ));

        if (game::$instance->getGameStateValue('endgame') == 1)
        {
            return 100;
        }
        
        else if ($nbre_hex_restant <= 40)
        {
            return 50;
        }

        else
        {
            return 0;
        }

        
    }


    /////////////////////////////////////////////////////////////////////////////////  
    //     _    _ _   _ _ _ _            __                  _   _                 
    //    | |  | | | (_) (_) |          / _|                | | (_)                
    //    | |  | | |_ _| |_| |_ _   _  | |_ _   _ _ __   ___| |_ _  ___  _ __  ___ 
    //    | |  | | __| | | | __| | | | |  _| | | | '_ \ / __| __| |/ _ \| '_ \/ __|
    //    | |__| | |_| | | | |_| |_| | | | | |_| | | | | (__| |_| | (_) | | | \__ \
    //     \____/ \__|_|_|_|\__|\__, | |_|  \__,_|_| |_|\___|\__|_|\___/|_| |_|___/
    //                           __/ |                                             
    //                          |___/                                              
    /////////////////////////////////////////////////////////////////////////////////  

    function addPending($player_id, $function, $arg = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL)
    {
        $sql = "INSERT INTO pending (player_id, function, arg, arg2, arg3, arg4) VALUES (" . $player_id . ", '" . $function . "', '" . $arg . "', '" . $arg2 . "', '" . $arg3 . "', '" . $arg4 . "')";
        self::DbQuery($sql);
    }


    function addPendingFirst($player_id, $function, $arg = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL)
    {
        $minid = self::getUniqueValueFromDB("select min(id) from pending") - 1;
        $sql = "INSERT INTO pending (id, player_id, function, arg, arg2) VALUES (" . $minid . "," . $player_id . ", '" . $function . "', '" . $arg . "', '" . $arg2 . "')";
        self::DbQuery($sql);
    }

    function checkArgs($arg1)
    {
        $ret = self::argPlayerTurn();

        if (!in_array($arg1, $ret['selectable']) && !in_array($arg1, $ret['selectable2']) && !in_array($arg1, $ret['selected']) && !in_array($arg1, $ret['buttons'])) {
            throw new feException("Not a valid selection");
        }
    }

    function getPariteDizaine($nombre)
    {
        // Extraire la dizaine
        $dizaine = (int)($nombre / 10) % 10;

        // Déterminer la parité (2 si paire, 1 si impaire)
        $parite = ($dizaine % 2 == 0) ? 2 : 1;

        return $parite;
    }

    function testMovePenguin($player_id, $hex)
    {
        $result = array();
        $exist = array();
        $paritehex = game::$instance->getPariteDizaine($hex);
        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE hex != 0", true);


        // direction 1
        $newhex = $hex;
        $stop = 0;
        while ($stop == 0) {

            $newhex = $newhex - 1;


            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }

        // direction 2
        $newhex = $hex;
        $newparite = $paritehex;
        $stop = 0;
        while ($stop == 0) {
            if ($newparite == 1) {
                $newhex = $newhex - 11;
                $newparite = 2;
            } else {
                $newhex = $newhex - 10;
                $newparite = 1;
            }



            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }


        // direction 3
        $newhex = $hex;
        $newparite = $paritehex;
        $stop = 0;
        while ($stop == 0) {
            if ($newparite == 1) {
                $newhex = $newhex - 10;
                $newparite = 2;
            } else {
                $newhex = $newhex - 9;
                $newparite = 1;
            }



            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }


        // direction 4
        $newhex = $hex;
        $stop = 0;
        while ($stop == 0) {

            $newhex = $newhex + 1;


            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }

        // direction 5
        $newhex = $hex;
        $newparite = $paritehex;
        $stop = 0;
        while ($stop == 0) {
            if ($newparite == 1) {
                $newhex = $newhex + 10;
                $newparite = 2;
            } else {
                $newhex = $newhex + 11;
                $newparite = 1;
            }



            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }


        // direction 6
        $newhex = $hex;
        $newparite = $paritehex;
        $stop = 0;
        while ($stop == 0) {
            if ($newparite == 1) {
                $newhex = $newhex + 9;
                $newparite = 2;
            } else {
                $newhex = $newhex + 10;
                $newparite = 1;
            }



            if ((!in_array($newhex, $hexoccuped)) && ($newhex >= 1)) {
                $exist = self::getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location = 'board' AND card_location_arg ='{$newhex}'");
                if ($exist != null) {
                    $result[] = $newhex;
                } else {
                    $stop = 1;
                }
            } else {
                $stop = 1;
            }
        }

        return $result;
    }

    // Stats turns

    function updateNbTurns($arg)
    {
        $player_id = self::getActivePlayerId();

        if($arg == "1")
        {
            $this->incStat(1, 'turns_number', $player_id);
        }

        if (self::getPlayerNoById($player_id) == 1) {
            $this->incStat(1, 'turns_number');
        }
    }



    ///////////////////////////////////////////////////////////////////////////////// 
    //     _____  _                                    _   _                 
    //    |  __ \| |                                  | | (_)                
    //    | |__) | | __ _ _   _  ___ _ __    __ _  ___| |_ _  ___  _ __  ___ 
    //    |  ___/| |/ _` | | | |/ _ \ '__|  / _` |/ __| __| |/ _ \| '_ \/ __|
    //    | |    | | (_| | |_| |  __/ |    | (_| | (__| |_| | (_) | | | \__ \
    //    |_|    |_|\__,_|\__, |\___|_|     \__,_|\___|\__|_|\___/|_| |_|___/
    //                     __/ |                                             
    //                    |___/                                              
    /////////////////////////////////////////////////////////////////////////////////


    public function actSelect(string $arg1)
    {

        self::checkArgs($arg1);

        $pending =  self::getObjectFromDB("SELECT* FROM pending order by id desc limit 1");
        $this->callPending($pending, true, $arg1);
        self::DbQuery("delete from pending where id=" . $pending['id']);
        //$this->giveExtraTime(self::getActivePlayerId());
        $this->gamestate->nextState('next');
    }

    public function actButton(string $arg1)
    {

        self::checkArgs($arg1);

        $pending =  self::getObjectFromDB("SELECT* FROM pending order by id desc limit 1");
        $this->callPending($pending, true, $arg1);
        self::DbQuery("delete from pending where id=" . $pending['id']);
        //$this->giveExtraTime(self::getActivePlayerId());
        $this->gamestate->nextState('next');
    }

    ///////////////////////////////////////////////////////////////////////////////// 
    //     _____                             _        _                                                    _       
    //    / ____|                           | |      | |                                                  | |      
    //    | |  __  __ _ _ __ ___   ___   ___| |_ __ _| |_ ___    __ _ _ __ __ _ _   _ _ __ ___   ___ _ __ | |_ ___ 
    //    | | |_ |/ _` | '_ ` _ \ / _ \ / __| __/ _` | __/ _ \  / _` | '__/ _` | | | | '_ ` _ \ / _ \ '_ \| __/ __|
    //    | |__| | (_| | | | | | |  __/ \__ \ || (_| | ||  __/ | (_| | | | (_| | |_| | | | | | |  __/ | | | |_\__ \
    //     \_____|\__,_|_| |_| |_|\___| |___/\__\__,_|\__\___|  \__,_|_|  \__, |\__,_|_| |_| |_|\___|_| |_|\__|___/
    //                                                                    __/ |                                   
    //                                                                   |___/                                    
    ///////////////////////////////////////////////////////////////////////////////// 


    public function argPlayerTurn()
    {
        $pending =  self::getObjectFromDB("SELECT* FROM pending order by id desc limit 1");
        $arg = $this->callPending($pending, false);

        return $arg;
    }


    ///////////////////////////////////////////////////////////////////////////////// 
    //      _____                            _        _                    _   _                 
    //     / ____|                          | |      | |                  | | (_)                
    //    | |  __  __ _ _ __ ___   ___   ___| |_ __ _| |_ ___    __ _  ___| |_ _  ___  _ __  ___ 
    //    | | |_ |/ _` | '_ ` _ \ / _ \ / __| __/ _` | __/ _ \  / _` |/ __| __| |/ _ \| '_ \/ __|
    //    | |__| | (_| | | | | | |  __/ \__ \ || (_| | ||  __/ | (_| | (__| |_| | (_) | | | \__ \
    //     \_____|\__,_|_| |_| |_|\___| |___/\__\__,_|\__\___|  \__,_|\___|\__|_|\___/|_| |_|___/
    //                                                                                       
    /////////////////////////////////////////////////////////////////////////////////     


    public function callPending($pending, $execute, $arg1 = null, $arg2 = null)
    {

        $obj = $this;
        if ($pending['player_id'] != null) {
            $obj = new Pending($pending['player_id']);
        }

        $fname = "";
        if (!$execute) {
            $fname .= "arg";
        }
        $fname .= $pending['function'];

        $ret = null;
        if (method_exists($obj, $fname)) {
            $ret = $obj->$fname($pending['arg'], $pending['arg2'], $arg1, $arg2);
        }

        return $ret;
    }


    public function stPending()
    {

        $pending =  self::getObjectFromDB("SELECT * FROM pending order by id desc limit 1");
        if ($pending == null) {
            $this->gamestate->nextState('end');
        } else {
            $args = $this->callPending($pending, false);

            ////////////// attention changement car si on donne la main a un autre joueur sans arg l'id de l active player ne change pas 
            if ($pending['player_id'] != self::getActivePlayerId()) {


                //change active player      
                $this->gamestate->changeActivePlayer($pending['player_id']);
                $this->gamestate->nextState('same');
            } else if ($args == null || (count($args['selectable']) == 0 && count($args['buttons']) == 0)) {
                //no args required, execute
                $this->callPending($pending, true);
                self::DbQuery("delete from pending where id=" . $pending['id']);
                $this->gamestate->nextState('same');
            } else {


                $this->gamestate->nextState('player');
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////// 
    //     _____  ____                                    _      
    //    |  __ \|  _ \                                  | |     
    //    | |  | | |_) |  _   _ _ __   __ _ _ __ __ _  __| | ___ 
    //    | |  | |  _ <  | | | | '_ \ / _` | '__/ _` |/ _` |/ _ \
    //    | |__| | |_) | | |_| | |_) | (_| | | | (_| | (_| |  __/
    //    |_____/|____/   \__,_| .__/ \__, |_|  \__,_|\__,_|\___|
    //                         | |     __/ |                     
    //                         |_|    |___/                      
    /////////////////////////////////////////////////////////////////////////////////  


    public function upgradeTableDb($from_version) {}




    /////////////////////////////////////////////////////////////////////////////////
    //    ______               _     _      
    //   |___  /              | |   (_)     
    //      / / ___  _ __ ___ | |__  _  ___ 
    //     / / / _ \| '_ ` _ \| '_ \| |/ _ \
    //    / /_| (_) | | | | | | |_) | |  __/
    //   /_____\___/|_| |_| |_|_.__/|_|\___|
    //                                   
    /////////////////////////////////////////////////////////////////////////////////     

    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default: {
                        $player_id = $this->getActivePlayerId();
                        self::DbQuery("delete from pending where player_id = {$player_id}");
                        $this->gamestate->nextState("zombiePass");
                        break;
                    }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
