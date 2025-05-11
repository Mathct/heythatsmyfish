<?php

namespace Bga\Games\heythatsmyfish;   // ATTENTION NOM DU JEU
use APP_GameClass;

//require_once 'actions/Actions.php'; // Inclure le fichier contenant les fonctions

class Pending extends APP_GameClass
{
    //use ActionsTrait; // ATTENTION

    public function __construct($player_id)
    {
        $this->player_id = $player_id;
        $p = self::getObjectFromDB("SELECT * FROM player WHERE player_id = {$player_id}");
        $this->player_no = $p['player_no'];
        $this->player_id = $p['player_id'];
        $this->player_name = $p['player_name'];
        $this->player_score = $p['player_score'];
        $this->player_color = $p['player_color'];

        $this->nb_player = count(self::getObjectListFromDB("SELECT player_id id FROM player", true));

        /// PREFERENCE DE CONFIRMATION

        $this->player_pref_confirm = self::getUniqueValueFromDB("SELECT pgp_value FROM bga_user_preferences WHERE pgp_player='{$this->player_id}' AND pgp_preference_id = 100");
    }

    function argFirstTurn($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must place a penguin');
        $ret['titleyou'] = clienttranslate('${you} must place a penguin');

        $nbpingouinsreserve = count(self::getObjectListFromDB("SELECT id FROM penguin WHERE player_id = '{$this->player_id}' AND hex = 0", true));

        $hexonefish = self::getObjectListFromDB("SELECT card_location_arg FROM tile WHERE card_type = 1 and card_location ='board'", true);
        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE hex != 0", true);

        if ($nbpingouinsreserve >= 1) {
            foreach ($hexonefish as $hexone) {
                if (!in_array($hexone, $hexoccuped)) {
                    $ret["selectable"][] = 'hex_' . $hexone;
                }
            }
        }




        return $ret;
    }

    function FirstTurn($parg1, $parg2, $varg1, $varg2)
    {


        if ($varg1 != null) {

            if($this->player_pref_confirm == 1)
            {

            $nbpingouinsonboard = count(self::getObjectListFromDB("SELECT id FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true));
            $no = $nbpingouinsonboard + 1;


            $explode = explode('_', $varg1);
            $hex = intval($explode[1]);
            self::DbQuery("UPDATE penguin set hex = $hex WHERE player_id = '{$this->player_id}' AND no = '{$no}'");

            $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$hex}'");

            game::$instance->notifyAllPlayers(
                'placePenguin',
                clienttranslate('${player_name} places a penguin'),
                array(
                    'player_name' => $this->player_name,
                    'penguin_infos' => $penguin_infos,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->addPendingFirst($this->player_id, "FirstTurn");
            }

            if($this->player_pref_confirm == 2)
            {

                game::$instance->addPending($this->player_id, "FirstTurnConfirm", $varg1);
            }
        } 

        else {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }
    }


    function argFirstTurnConfirm($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must place a penguin');
        $ret['titleyou'] = clienttranslate('${you} must confirm');

        $ret["selected"][] = $parg1;


        $ret['buttons'][] = 'yes';
        $ret['buttons'][] = 'no';




        return $ret;
    }

    function FirstTurnConfirm($parg1, $parg2, $varg1, $varg2)
    {


        if ($varg1 == 'yes') {
            $nbpingouinsonboard = count(self::getObjectListFromDB("SELECT id FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true));
            $no = $nbpingouinsonboard + 1;


            $explode = explode('_', $parg1);
            $hex = intval($explode[1]);
            self::DbQuery("UPDATE penguin set hex = $hex WHERE player_id = '{$this->player_id}' AND no = '{$no}'");

            $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$hex}'");

            game::$instance->notifyAllPlayers(
                'placePenguin',
                clienttranslate('${player_name} places a penguin'),
                array(
                    'player_name' => $this->player_name,
                    'penguin_infos' => $penguin_infos,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->addPendingFirst($this->player_id, "FirstTurn");
        }

        if ($varg1 == 'no') {
            game::$instance->addPending($this->player_id, "FirstTurn");
        }
    }




    function argNormalTurn($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} must select a penguin to move');

        
        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);

        foreach ($hexoccuped as $hex) {
            $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
            
            if (count($listhex)>=1)
            {
                $ret["selectable"][] = 'hex_' . $hex;
            }
        }


        return $ret;
    }

    function NormalTurn($parg1, $parg2, $varg1, $varg2)
    {

        if($varg1 != null)
        {
            game::$instance->addPending($this->player_id, "NormalTurn2", $varg1);
        }

        else
        {
            game::$instance->addPending($this->player_id, "Remove");
        }

       
    }

    function argNormalTurn2($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile or change penguin');

        $ret["selected"][] = $parg1;

        $explode = explode('_', $parg1);

        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);

        foreach ($hexoccuped as $hex) {
                        
            if ($hex != $explode[1])
            {
                $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
            
                if (count($listhex)>=1)
                {
                    $ret["selectable2"][] = 'hex_' . $hex;
                }

            }
        }


        $tiles = game::$instance->testMovePenguin($this->player_id, $explode[1]);

        foreach ($tiles as $tile)
        {
            $ret["selectable"][] = 'hex_' . $tile; 
        }
        
        
        $ret['buttons'][] = 'cancel';
        

        return $ret;
    }

    function NormalTurn2($parg1, $parg2, $varg1, $varg2)
    {
        

        if($varg1 == 'cancel')

        {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }

        else

        {
            
            $test = 0;
            $explode = explode('_', $varg1);
            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);

            if (in_array($explode[1], $hexoccuped)) {

                $test = 1;
                
            }

            if ($test == 0)
            {

                if($this->player_pref_confirm == 1)
                {
                $explode1 = explode('_', $parg1);
                $explode2 = explode('_', $varg1);

                $starthex = intval($explode1[1]);
                $newhex = intval($explode2[1]);

                self::DbQuery("UPDATE penguin set hex = $newhex WHERE player_id = '{$this->player_id}' AND hex = '{$starthex}'");
                self::DbQuery("UPDATE player set player_new_tile = '{$newhex}' WHERE player_id = '{$this->player_id}'");
                self::DbQuery("UPDATE tile set card_location = {$this->player_id} WHERE card_location_arg = '{$starthex}'");

                $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$newhex}'");

                $fish = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$starthex}'");
                $sprite = self::getUniqueValueFromDB("SELECT card_type_arg FROM tile WHERE card_location_arg = '{$starthex}'");

                $last_tile = $fish.'_'.$sprite;
                self::DbQuery("UPDATE player set player_last_tile = '{$last_tile}' WHERE player_id = '{$this->player_id}'");

                game::$instance->notifyAllPlayers(
                    'movePenguin',
                    clienttranslate('${player_name} moves a penguin and collects ${nb} fish'),
                    array(
                        'player_name' => $this->player_name,
                        'starthex' => $starthex,
                        'penguin_infos' => $penguin_infos,
                        'nb' => $fish,
                        'last_tile' => $last_tile,

                    )
                );

                game::$instance->giveExtraTime($this->player_id);
                game::$instance->updateNbTurns(1);
                game::$instance->incStat(1, 'tiles_collected', $this->player_id);
                game::$instance->incStat($fish, 'fish_collected', $this->player_id);


                /// TEST ISOLATED PENGUIN

                $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);
                
                if (($index = array_search($newhex, $hex_occuped)) !== false) { // permet de mettre newhex en dernier
                unset($hex_occuped[$index]);                        // Supprime la valeur cible
                $hex_occuped = array_values($hex_occuped);          // Réindexe proprement le tableau
                array_push($hex_occuped, $newhex);                  // Ajoute la valeur cible à la fin
                }

                foreach($hex_occuped as $hex)
                {
                    $test_isolate = game::$instance->testIsolatedPenguin($hex);
                    if($test_isolate == true)
                    {
                        $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                        if($tiles != null)
                        {
                            game::$instance->addPending($this->player_id, "Isolate", $hex);
                        }
                        else{
                            game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                        }
                    }

                }

                game::$instance->addPendingFirst($this->player_id, "NormalTurn");

                                
                }

                if($this->player_pref_confirm == 2)
                {

                    game::$instance->addPending($this->player_id, "NormalTurn2Confirm", $parg1, $varg1);
                }
            }

            

            if ($test == 1)
            {    
                if($varg1 == $parg1)
                {
                    game::$instance->addPending($this->player_id, "NormalTurn");
                } 
                else
                {
                    game::$instance->addPending($this->player_id, "NormalTurn2", $varg1);
                }       
                
            }


        }

        
    }


    function argNormalTurn2Confirm($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} must confirm');

        $ret["selected"][] = $parg1;
        $ret["selected"][] = $parg2;


        $ret['buttons'][] = 'yes';
        $ret['buttons'][] = 'no';
        

        return $ret;
    }

    function NormalTurn2Confirm($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == 'no')

        {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }

        if($varg1 == 'yes')

        {
            $explode1 = explode('_', $parg1);
            $explode2 = explode('_', $parg2);

            $starthex = intval($explode1[1]);
            $newhex = intval($explode2[1]);

            self::DbQuery("UPDATE penguin set hex = $newhex WHERE player_id = '{$this->player_id}' AND hex = '{$starthex}'");
            self::DbQuery("UPDATE player set player_new_tile = '{$newhex}' WHERE player_id = '{$this->player_id}'");
            self::DbQuery("UPDATE tile set card_location = {$this->player_id} WHERE card_location_arg = '{$starthex}'");

            $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$newhex}'");

            $fish = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$starthex}'");
            $sprite = self::getUniqueValueFromDB("SELECT card_type_arg FROM tile WHERE card_location_arg = '{$starthex}'");

            $last_tile = $fish.'_'.$sprite;
            self::DbQuery("UPDATE player set player_last_tile = '{$last_tile}' WHERE player_id = '{$this->player_id}'");

            game::$instance->notifyAllPlayers(
                'movePenguin',
                clienttranslate('${player_name} moves a penguin and collects ${nb} fish'),
                array(
                    'player_name' => $this->player_name,
                    'starthex' => $starthex,
                    'penguin_infos' => $penguin_infos,
                    'nb' => $fish,
                    'last_tile' => $last_tile,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->incStat(1, 'tiles_collected', $this->player_id);
            game::$instance->incStat($fish, 'fish_collected', $this->player_id);
            
            /// TEST ISOLATED PENGUIN

            $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);
            
            if (($index = array_search($newhex, $hex_occuped)) !== false) { // permet de mettre newhex en dernier
            unset($hex_occuped[$index]);                        // Supprime la valeur cible
            $hex_occuped = array_values($hex_occuped);          // Réindexe proprement le tableau
            array_push($hex_occuped, $newhex);                  // Ajoute la valeur cible à la fin
            }

            foreach($hex_occuped as $hex)
            {
                $test_isolate = game::$instance->testIsolatedPenguin($hex);
                if($test_isolate == true)
                {
                    $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                    if($tiles != null)
                    {
                        game::$instance->addPending($this->player_id, "Isolate", $hex);
                    }
                    else{
                        game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                    }
                }

            }

            game::$instance->addPendingFirst($this->player_id, "NormalTurn");

        }

        
    }




    function argRemove($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} can\'t play anymore');


        
        
        

        return $ret;
    }

    function Remove($parg1, $parg2, $varg1, $varg2)
    {
        $penguin_infos = self::getObjectListFromDB("SELECT id, player_id, no, hex FROM penguin WHERE player_id='{$this->player_id}'");

        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}'", true);
        $fish_type = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$hexoccuped[0]}'");
        $sprite = self::getUniqueValueFromDB("SELECT card_type_arg FROM tile WHERE card_location_arg = '{$hexoccuped[0]}'");

        $last_tile = $fish_type.'_'.$sprite;
        self::DbQuery("UPDATE player set player_last_tile = '{$last_tile}' WHERE player_id = '{$this->player_id}'");

        $fish_gain = 0;

        self::DbQuery("UPDATE penguin set hex = 0 WHERE player_id = '{$this->player_id}'");
        self::DbQuery("UPDATE player set player_new_tile = 0 WHERE player_id = '{$this->player_id}'");

        foreach($penguin_infos as $penguin)
        {
            $fish = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$penguin['hex']}'");
            self::DbQuery("UPDATE tile set card_location = {$this->player_id} WHERE card_location_arg = '{$penguin['hex']}'");

            game::$instance->incStat(1, 'tiles_collected', $this->player_id);
            game::$instance->incStat($fish, 'fish_collected', $this->player_id);

            $fish_gain = $fish_gain + $fish;
        }

        game::$instance->notifyAllPlayers(
            'removePenguins',
            clienttranslate('${player_name} can\'t move penguins anymore'),
            array(
                'player_name' => $this->player_name,
                'penguin_infos' => $penguin_infos,
                'last_tile' => $last_tile,

            )
        );

        game::$instance->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} collects ${nb} fish'),
            array(
                'player_name' => $this->player_name,
                'nb' => $fish_gain,
                

            )
        );

                
        $end = count(self::getObjectListFromDB( "SELECT id FROM penguin WHERE hex != 0", true ));

        if($end >= 1)
        {
            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(0);
            game::$instance->addPendingFirst($this->player_id, "Pass");
        }

        else

        {
            //game::$instance->notifyAllPlayers( 'simplePause', '', [ 'time' => 2000] ); 

            $all_players_id = self::getObjectListFromDB( "SELECT player_id FROM player", true );
            foreach ($all_players_id as $player_id)
            {
                $fish = 0;
                $tile = count(self::getObjectListFromDB( "SELECT card_id FROM tile WHERE card_location = '{$player_id}'", true ));

                $score_fish = self::getObjectListFromDB( "SELECT card_type FROM tile WHERE card_location = '{$player_id}'", true );
                foreach ($score_fish as $score)
                {
                    $fish = $fish + $score;
                }

                self::DbQuery("UPDATE player set player_score = {$fish} WHERE player_id = '{$player_id}'");
                self::DbQuery("UPDATE player set player_score_aux = {$tile} WHERE player_id = '{$player_id}'");



                game::$instance->notifyAllPlayers(
                    'score',
                    '',
                    array(
                        'player_id' => $player_id,
                        'total_fish' => $fish,

                    )
                );
                
            }

            game::$instance->setGameStateValue("endgame", 1); // pour progression
            game::$instance->addPending($this->player_id, "End");   

        }
        
        
        
    }

    function argPass($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} can\'t play anymore');


        
        

        return $ret;
    }

    function Pass($parg1, $parg2, $varg1, $varg2)
    {
        $penguin_infos = self::getObjectListFromDB("SELECT id, player_id, no, hex FROM penguin WHERE player_id='{$this->player_id}'");

        game::$instance->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} cannot play anymore and passes'),
            array(
                'player_name' => $this->player_name,
                

            )
        );
        
        
        game::$instance->giveExtraTime($this->player_id);
        game::$instance->updateNbTurns(0);
        game::$instance->addPendingFirst($this->player_id, "Pass");
        
    }

    function argEnd($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} can\'t play anymore');


        
        

        return $ret;
    }

    function End($parg1, $parg2, $varg1, $varg2)
    {
        
        game::$instance->gamestate->nextState('end');
    }



    ////////////////// ISOLATE ////////////////

    function argIsolate($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must do all the movements of the isolated penguins');
        $ret['titleyou'] = clienttranslate('This penguin is isolated. ${you} must complete all of its movements');


        $ret["selected"][] = 'hex_' . $parg1;


        $tiles = game::$instance->testMovePenguin($this->player_id, $parg1);
        foreach ($tiles as $tile)
        {
            $ret["selectable"][] = 'hex_' . $tile; 
        }


               

        return $ret;
    }

    function Isolate($parg1, $parg2, $varg1, $varg2)
    {
        $explode2 = explode('_', $varg1);

        $starthex = intval($parg1);
        $newhex = intval($explode2[1]);


        if($this->player_pref_confirm == 1){

        self::DbQuery("UPDATE penguin set hex = $newhex WHERE player_id = '{$this->player_id}' AND hex = '{$starthex}'");
        self::DbQuery("UPDATE player set player_new_tile = '{$newhex}' WHERE player_id = '{$this->player_id}'");
        self::DbQuery("UPDATE tile set card_location = {$this->player_id} WHERE card_location_arg = '{$starthex}'");

        $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$newhex}'");

        $fish = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$starthex}'");
        $sprite = self::getUniqueValueFromDB("SELECT card_type_arg FROM tile WHERE card_location_arg = '{$starthex}'");

        $last_tile = $fish.'_'.$sprite;
        self::DbQuery("UPDATE player set player_last_tile = '{$last_tile}' WHERE player_id = '{$this->player_id}'");

        game::$instance->notifyAllPlayers(
            'movePenguin',
            clienttranslate('${player_name} moves a penguin and collects ${nb} fish'),
            array(
                'player_name' => $this->player_name,
                'starthex' => $starthex,
                'penguin_infos' => $penguin_infos,
                'nb' => $fish,
                'last_tile' => $last_tile,

            )
        );

        game::$instance->giveExtraTime($this->player_id);
        game::$instance->updateNbTurns(1);
        game::$instance->incStat(1, 'tiles_collected', $this->player_id);
        game::$instance->incStat($fish, 'fish_collected', $this->player_id);

        $tiles = game::$instance->testMovePenguin($this->player_id, $newhex);

        if($tiles != null)
        {
            game::$instance->addPending($this->player_id, "Isolate", $newhex);
        }
        }

        if($this->player_pref_confirm == 2){
            game::$instance->addPending($this->player_id, "IsolateConfirm", $starthex, $newhex);
        }

               
        
    }


    function argIsolateConfirm($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must make all the movements of the isolated Penguin');
        $ret['titleyou'] = clienttranslate('${you} must confirm');

        $ret["selected"][] = 'hex_'.$parg1;
        $ret["selected"][] = 'hex_'.$parg2;


        $ret['buttons'][] = 'yes';
        $ret['buttons'][] = 'no';


        

        
        

        return $ret;
    }

    function IsolateConfirm($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == 'no')
        {
            game::$instance->addPending($this->player_id, "Isolate", $parg1);
        }

        if($varg1 == 'yes')
        {
            $starthex = $parg1;
            $newhex = $parg2;

            self::DbQuery("UPDATE penguin set hex = $newhex WHERE player_id = '{$this->player_id}' AND hex = '{$starthex}'");
            self::DbQuery("UPDATE player set player_new_tile = '{$newhex}' WHERE player_id = '{$this->player_id}'");
            self::DbQuery("UPDATE tile set card_location = {$this->player_id} WHERE card_location_arg = '{$starthex}'");

            $penguin_infos = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$newhex}'");

            $fish = self::getUniqueValueFromDB("SELECT card_type FROM tile WHERE card_location_arg = '{$starthex}'");
            $sprite = self::getUniqueValueFromDB("SELECT card_type_arg FROM tile WHERE card_location_arg = '{$starthex}'");

            $last_tile = $fish.'_'.$sprite;
            self::DbQuery("UPDATE player set player_last_tile = '{$last_tile}' WHERE player_id = '{$this->player_id}'");

            game::$instance->notifyAllPlayers(
                'movePenguin',
                clienttranslate('${player_name} moves a penguin and collects ${nb} fish'),
                array(
                    'player_name' => $this->player_name,
                    'starthex' => $starthex,
                    'penguin_infos' => $penguin_infos,
                    'nb' => $fish,
                    'last_tile' => $last_tile,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->incStat(1, 'tiles_collected', $this->player_id);
            game::$instance->incStat($fish, 'fish_collected', $this->player_id);

            $tiles = game::$instance->testMovePenguin($this->player_id, $newhex);

            if($tiles != null)
            {
                game::$instance->addPending($this->player_id, "Isolate", $newhex);
            }
            
        }
        
        
        
    }










}
