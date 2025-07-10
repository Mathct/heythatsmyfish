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


        /// VARIANTE

        $this->nextplayer = game::$instance->getPlayerAfter($this->player_id);
        $this->nextplayer_name = self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id='{$this->nextplayer}'");
        $this->nextplayer_color = self::getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id='{$this->nextplayer}'");

        if((game::$instance->getGameStateValue('variant_mode') == 0)||(game::$instance->getGameStateValue('variant_mode')==1)) // 0 si le jeu est en cours à la MAJ
        {
            $this->variante = 1;
        }

        if(game::$instance->getGameStateValue('variant_mode') == 2)

        {
            $this->variante = 2;
        }

        if(game::$instance->getGameStateValue('variant_mode') == 3)

        {
            $this->variante = 3;
        }



        
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


            
            $nbpingouinsreserve = count(self::getObjectListFromDB("SELECT id FROM penguin WHERE player_id = '{$this->player_id}' AND hex = 0", true));

            if($nbpingouinsreserve >= 1)
            {
                game::$instance->addPendingFirst($this->player_id, "FirstTurn");

            }

            if($nbpingouinsreserve == 0)
            {

                if(($this->variante == 1)||($this->variante == 3))
                {
                    game::$instance->addPendingFirst($this->player_id, "NormalTurn");

                }

                if($this->variante == 2)
                {
                    $nbre_players = count(self::getObjectListFromDB( "SELECT player_id FROM player", true ));
                    $player_no = self::getUniqueValueFromDB("SELECT player_no FROM player WHERE player_id={$this->player_id}");
                    if($player_no == $nbre_players)
                    {
                        game::$instance->setGameStateValue("variant_imposed_hex", 0);
                        game::$instance->setGameStateValue("variant_one_hex", 0);
                        game::$instance->addPending($this->player_id, "Variant");
                    }
                    else
                    {
                        game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                    }

                }

            }



        }

            if($this->player_pref_confirm == 2)
            {

                game::$instance->addPending($this->player_id, "FirstTurnConfirm", $varg1);
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
            
            $nbpingouinsreserve = count(self::getObjectListFromDB("SELECT id FROM penguin WHERE player_id = '{$this->player_id}' AND hex = 0", true));

            if($nbpingouinsreserve >= 1)
            {
                game::$instance->addPendingFirst($this->player_id, "FirstTurn");

            }

            if($nbpingouinsreserve == 0)
            {

                if(($this->variante == 1)||($this->variante == 3))
                {
                    game::$instance->addPendingFirst($this->player_id, "NormalTurn");

                }

                if($this->variante == 2)
                {
                    $nbre_players = count(self::getObjectListFromDB( "SELECT player_id FROM player", true ));
                    $player_no = self::getUniqueValueFromDB("SELECT player_no FROM player WHERE player_id={$this->player_id}");
                    if($player_no == $nbre_players)
                    {
                        game::$instance->setGameStateValue("variant_imposed_hex", 0);
                        game::$instance->setGameStateValue("variant_one_hex", 0);
                        game::$instance->addPending($this->player_id, "Variant");
                    }
                    else
                    {
                        game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                    }

                }

            }
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

        if(game::$instance->getGameStateValue("variant_imposed_hex") >= 1)
        {
            //rien
        }

        else
        {
        
            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            $table = array();

            foreach ($hexoccuped as $hex) {
                $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
                
                if (count($listhex)>=1)
                {
                    $table[] = 'hex_' . $hex;
                    
                }

                else
                {
                    if($this->variante == 3)
                    {
                        if(count(game::$instance->canPush($hex)) >=1)
                        {
                            $table[] = 'hex_' . $hex;
                        }
                    }
                }
            }

            if(count($table) >= 2)
            {
                $ret["selectable"] = $table;
            }


        }

        // il faut que je teste ici le cas où le joueur n'a qu'un seul ping à bouger

        return $ret;
    }

    function NormalTurn($parg1, $parg2, $varg1, $varg2)
    {

        if($varg1 != null)
        {
            if($this->variante == 3)
            {
                game::$instance->addPending($this->player_id, "Pushing", $varg1);
            }

            else
            {
            game::$instance->addPending($this->player_id, "NormalTurn2", $varg1);
            }
        }

        else
        {

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
            $array = array();

            foreach ($hexoccuped as $test) {
                $listhex = game::$instance->testMovePenguin($this->player_id, $test);
                
                if (count($listhex)>=1)
                {
                    $array[] = $test;
                }

                else
                {
                    if($this->variante == 3)
                    {
                        if(count(game::$instance->canPush($test)) >=1)
                        {
                            $array[] = $test;
                        }
                    }
                }
            }

            


            if((game::$instance->getGameStateValue("variant_imposed_hex") >= 1)&&($array != null))
            {
                $hex = 'hex_' . game::$instance->getGameStateValue("variant_imposed_hex");
                game::$instance->addPending($this->player_id, "NormalTurn2", $hex);
            }

           elseif((game::$instance->getGameStateValue("variant_imposed_hex") == 0)&&(count($array)== 1)&&($this->variante != 3))
            {
                game::$instance->addPending($this->player_id, "NormalTurn2", 'hex_' .$array[0]);
            }

            elseif((game::$instance->getGameStateValue("variant_imposed_hex") == 0)&&(count($array)== 1)&&($this->variante == 3))
            {
                game::$instance->addPending($this->player_id, "Pushing", 'hex_' .$array[0]);
            }

            else
            {
                game::$instance->addPending($this->player_id, "Remove");
            }

            
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
        

        $ret["selected"][] = $parg1;

        $explode = explode('_', $parg1);
        $tiles = game::$instance->testMovePenguin($this->player_id, $explode[1]);
        

        foreach ($tiles as $tile)
        {
            $ret["selectable"][] = 'hex_' . $tile; 
        }

        if(game::$instance->getGameStateValue("variant_imposed_hex") >= 1)
        {
            $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile');
        }

        else
        {
            

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            foreach ($hexoccuped as $hex) {
                            
                if ($hex != $explode[1])
                {
                    $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
                
                    if (count($listhex)>=1)
                    {
                        $ret["selectable2"][] = 'hex_' . $hex;
                    }

                    else{
                        if($this->variante == 3)
                        {
                            if(count(game::$instance->canPush($hex)) >=1)
                            {
                                $ret["selectable2"][] = 'hex_' . $hex;
                            }
                        }

                    }

                }
            }

            if(count($ret["selectable2"]) > 1)
            {
                $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile or change penguin');
                $ret['buttons'][] = 'cancel';

            }

            else
            {
                $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile');
            }
            
            
            

        }
        
        
        
        return $ret;
    }

    function NormalTurn2($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == null)
        {
            game::$instance->addPending($this->player_id, "Pushing", $parg1);
        }
        

        else if($varg1 == 'cancel')

        {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }

        else

        {
            
            $test = 0;
            $explode = explode('_', $varg1);
            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

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
                            'pushing' => 0,

                        )
                    );

                    game::$instance->giveExtraTime($this->player_id);
                    game::$instance->updateNbTurns(1);
                    game::$instance->incStat(1, 'tiles_collected', $this->player_id);
                    game::$instance->incStat($fish, 'fish_collected', $this->player_id);

                    if(game::$instance->getGameStateValue('scoring_mode') == 2)
                    {
                        game::$instance->Score();
                    }

                    // VARIANTE

                    if($this->variante == 2)

                    {
                        game::$instance->addPending($this->player_id, "Variant");
                        game::$instance->setGameStateValue("variant_imposed_hex", 0);
                        game::$instance->setGameStateValue("variant_one_hex", 0);
                    
                    }

                    

                    if($this->variante == 3){

                        
                        game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                        game::$instance->setGameStateValue("variant_imposed_hex", 0);
                        game::$instance->setGameStateValue("variant_one_hex", 0);
                    }


                    if($this->variante == 1)

                    {

                        /// TEST ISOLATED PENGUIN

                        $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
                        
                        $count_ping = count($hex_occuped);

                        $count_isolate = 0;
                        $count_blocked = 0;

                        foreach($hex_occuped as $hex)
                        {
                            $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                            if($tiles == null)
                            {
                                $count_blocked = $count_blocked +1;
                            }

                            else
                            {

                                $test_isolate = game::$instance->testIsolatedPenguin($hex);
                                if($test_isolate == true)
                                {
                                    $count_isolate = $count_isolate +1;
                                        
                                                        
                                }
                            }

                        }

                        if(($count_blocked == $count_ping)||($count_isolate + $count_blocked != $count_ping))
                        {
                            game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                            
                        }

                        
                        else
                        {
                           
                            game::$instance->addPending($this->player_id, "Isolate");
                        }

                        game::$instance->setGameStateValue("variant_imposed_hex", 0);
                        game::$instance->setGameStateValue("variant_one_hex", 0);
                        

                    }
              
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
                    if(($this->variante == 1)||($this->variante == 2))
                    {
                        game::$instance->addPending($this->player_id, "NormalTurn2", $varg1);
                    }
                    if($this->variante == 3)
                    {
                        game::$instance->addPending($this->player_id, "Pushing", $varg1);
                    }
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
                    'pushing' => 0,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->incStat(1, 'tiles_collected', $this->player_id);
            game::$instance->incStat($fish, 'fish_collected', $this->player_id);

            if(game::$instance->getGameStateValue('scoring_mode') == 2)
            {
                game::$instance->Score();
            }

            // VARIANTE

            if($this->variante == 2)

            {
                game::$instance->addPending($this->player_id, "Variant");
                game::$instance->setGameStateValue("variant_imposed_hex", 0);
                game::$instance->setGameStateValue("variant_one_hex", 0);
                

            }

            

            if($this->variante == 3){

                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                game::$instance->setGameStateValue("variant_imposed_hex", 0);
                game::$instance->setGameStateValue("variant_one_hex", 0);
            }


            if($this->variante == 1)

            {

            
            /// TEST ISOLATED PENGUIN

            $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            $count_ping = count($hex_occuped);
            
            $count_isolate = 0;
            $count_blocked = 0;

            foreach($hex_occuped as $hex)
            {
                $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                if($tiles == null)
                {
                    $count_blocked = $count_blocked +1;
                }

                else
                {

                    $test_isolate = game::$instance->testIsolatedPenguin($hex);
                    if($test_isolate == true)
                    {
                        $count_isolate = $count_isolate +1;
                            
                                            
                    }
                }

            }

            if(($count_blocked == $count_ping)||($count_isolate + $count_blocked != $count_ping))
            {
                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                
            }

            
            else
            {
                
                game::$instance->addPending($this->player_id, "Isolate");
            }

            game::$instance->setGameStateValue("variant_imposed_hex", 0);
            game::$instance->setGameStateValue("variant_one_hex", 0);
            

            }

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
        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
        if(count($hexoccuped) != 0)
        {
        $penguin_infos = self::getObjectListFromDB("SELECT id, player_id, no, hex FROM penguin WHERE player_id='{$this->player_id}'");

        
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

    }

        if(game::$instance->getGameStateValue('scoring_mode') == 2)
        {
            game::$instance->Score();
        }

                
        $end = count(self::getObjectListFromDB( "SELECT id FROM penguin WHERE hex != 0", true ));

        if($end >= 1)
        {
            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->addPendingFirst($this->player_id, "Pass");
            

        }

        else

        {
            
            game::$instance->Score();
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
        
        
        game::$instance->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} cannot play anymore and passes'),
            array(
                'player_name' => $this->player_name,
                

            )
        );
        
        
        game::$instance->giveExtraTime($this->player_id);
        game::$instance->updateNbTurns(1);
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
        $ret['titleyou'] = clienttranslate('${you} must complete the movements of the isolated penguins');


        $table = array();
        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

        foreach ($hexoccuped as $hex) {
            $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
            
            if (count($listhex)>=1)
            {
                $table[] = 'hex_' . $hex;
            }
        }

        if(count($table) >= 2)
        {
            $ret["selectable"] = $table;
        }
               

        return $ret;
    }

    function Isolate($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 != null)
        {
            game::$instance->addPending($this->player_id, "IsolateStep2", $varg1);
        }

        else{

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
            $table = array();

            foreach ($hexoccuped as $hex) {
                $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
                
                if (count($listhex)>=1)
                {
                    $table[] = 'hex_' . $hex;
                }
            }

            
                game::$instance->addPending($this->player_id, "IsolateStep2", $table[0],1);
            
            
        }
               
        
    }

    function argIsolateStep2($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must do all the movements of the isolated penguins');
        
        $ret["selected"][] = $parg1;


        $explode = explode('_', $parg1);

        $tiles = game::$instance->testMovePenguin($this->player_id, $explode[1]);

        foreach ($tiles as $tile)
        {
            $ret["selectable"][] = 'hex_' . $tile; 
        }

        if($parg2 == 1)
        {
            $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile');
           
            
         
        }

        else
        {
            $ret['titleyou'] = clienttranslate('${you} must select an ice floe tile or change penguin');

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

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

            $ret['buttons'][] = 'cancel';  
            


        }

       
             

        return $ret;
    }

    function IsolateStep2($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == 'cancel')
        {
            game::$instance->addPending($this->player_id, "Isolate");
        }
       
        else{

            $test = 0;
            $explode = explode('_', $varg1);
            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

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
                            'pushing' => 0,

                        )
                    );

                    game::$instance->giveExtraTime($this->player_id);
                    game::$instance->updateNbTurns(1);
                    game::$instance->incStat(1, 'tiles_collected', $this->player_id);
                    game::$instance->incStat($fish, 'fish_collected', $this->player_id);

                    if(game::$instance->getGameStateValue('scoring_mode') == 2)
                    {
                        game::$instance->Score();
                    }


                    $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
                    $table = array();

                    foreach ($hexoccuped as $hex) {
                        $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
                        
                        if (count($listhex)>=1)
                        {
                            $table[] = 'hex_' . $hex;
                        }
                    }

                    if($table != null)
                    {
                        if(count($table) == 1)
                        {
                            game::$instance->addPending($this->player_id, "IsolateStep2", $table[0],1);
                        }
                        else
                        {
                            game::$instance->addPending($this->player_id, "Isolate");
                        }

                        
                        
                        
                    }
                    else
                    {
                        game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                    }



                }

                if($this->player_pref_confirm == 2)
                {

                    game::$instance->addPending($this->player_id, "IsolateConfirm", $parg1, $varg1);
                }

                
            }

            if ($test == 1)
            {    
                if($varg1 == $parg1)
                {
                    game::$instance->addPending($this->player_id, "Isolate");
                } 
                else
                {
                    game::$instance->addPending($this->player_id, "IsolateStep2", $varg1);
                }       
                
            }
               
        
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

        $ret["selected"][] = $parg1;
        $ret["selected"][] = $parg2;


        $ret['buttons'][] = 'yes';
        $ret['buttons'][] = 'no';


        

        
        

        return $ret;
    }

    function IsolateConfirm($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == 'no')
        {
            game::$instance->addPending($this->player_id, "Isolate");
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
                    'pushing' => 0,

                )
            );

            game::$instance->giveExtraTime($this->player_id);
            game::$instance->updateNbTurns(1);
            game::$instance->incStat(1, 'tiles_collected', $this->player_id);
            game::$instance->incStat($fish, 'fish_collected', $this->player_id);

            if(game::$instance->getGameStateValue('scoring_mode') == 2)
            {
                game::$instance->Score();
            }

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);
            $table = array();

            foreach ($hexoccuped as $hex) {
                $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
                
                if (count($listhex)>=1)
                {
                    $table[] = 'hex_' . $hex;
                }
            }

            if($table != null)
            {
                if(count($table) == 1)
                {
                    game::$instance->addPending($this->player_id, "IsolateStep2", $table[0],1);
                }
                else
                {
                    game::$instance->addPending($this->player_id, "Isolate");
                }

                
                
                
            }
            else
            {
                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
            }
            
        }
        
        
        
    }

//// VARIANT SLEEPY

function argVariant($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        
        $nextplayer = game::$instance->NextPlayerNoBlocked($this->player_id);
        if($nextplayer != 0)
        {
            $nextplayer_name = self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id={$nextplayer}");
            $nextplayer_color = self::getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id={$nextplayer}");
                    
            $ret['opponent'] = '<span style="color: #' . $nextplayer_color . ';">' . $nextplayer_name . '</span>';

            $ret['title'] = clienttranslate('"Sleepy Penguins": ${actplayer} must force a #opponent#\'s penguin to move');
            $ret['titleyou'] = clienttranslate('"Sleepy Penguins": ${you} must force a #opponent#\'s penguin to move');

        
            $peng_selectable = array();

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$nextplayer}' AND hex != 0", true);

            if($hexoccuped != null)

            {

            foreach ($hexoccuped as $hex) {
                $listhex = game::$instance->testMovePenguin($nextplayer, $hex);
                
                if (count($listhex)>=1)
                {
                    if(game::$instance->testIsolatedPenguin($hex) == false)
                    {
                        $peng_selectable[] = 'hex_' . $hex;
                    }
                    
                }
                
            }

            

            if(count($peng_selectable) >= 2)
            {
                $ret["selectable"] = $peng_selectable;
            }
            if(count($peng_selectable) == 1)
            {
                $explode = explode('_', $peng_selectable[0]);
                $one_hex = intval($explode[1]);
                game::$instance->setGameStateValue("variant_one_hex", $one_hex);
            }



            }

        }


        return $ret;
    }

    function Variant($parg1, $parg2, $varg1, $varg2)
    {
        $nextplayer = game::$instance->NextPlayerNoBlocked($this->player_id);

        $nextplayer_name = self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id={$nextplayer}");
        $nextplayer_color = self::getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id={$nextplayer}");

        /// TEST ISOLATED PENGUIN

            $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            $count_ping = count($hex_occuped);
            
            /*if (($index = array_search($newhex, $hex_occuped)) !== false) { // permet de mettre newhex en dernier
            unset($hex_occuped[$index]);                        // Supprime la valeur cible
            $hex_occuped = array_values($hex_occuped);          // Réindexe proprement le tableau
            array_push($hex_occuped, $newhex);                  // Ajoute la valeur cible à la fin
            }*/

            $count_isolate = 0;
            $count_blocked = 0;

            foreach($hex_occuped as $hex)
            {
                $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                if($tiles == null)
                {
                    $count_blocked = $count_blocked +1;
                }

                else
                {

                    $test_isolate = game::$instance->testIsolatedPenguin($hex);
                    if($test_isolate == true)
                    {
                        $count_isolate = $count_isolate +1;
                            
                                            
                    }
                }

            }

        if($varg1 != null)
        {
                        
            if($this->player_pref_confirm == 1)
            {
                $explode = explode('_', $varg1);
                $hex = intval($explode[1]);
                game::$instance->setGameStateValue("variant_one_hex", 0);
                game::$instance->setGameStateValue("variant_imposed_hex", $hex);
                game::$instance->giveExtraTime($this->player_id);
                

                game::$instance->notifyAllPlayers(
                'message',
                clienttranslate('${player_name} forces ${next} to move a penguin'),
                array(
                    'next' =>    [   'log' => '<b style="color: #${color};">${opponent_name}</b>',
                                        'args'=> ['opponent_name' => $nextplayer_name, 'color'=>$nextplayer_color]
                                    ],

                    'player_name' => $this->player_name,
                    

                )
                );

                if(($count_blocked == $count_ping)||($count_isolate + $count_blocked != $count_ping))
                {
                    game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                    
                }

            
                else
                {
                    
                    game::$instance->addPending($this->player_id, "Isolate");
                }
                
            }

            if($this->player_pref_confirm == 2)
            {
                game::$instance->addPending($this->player_id, "VariantConfirm", $varg1);
            }
        }

        else
        {
            
           
            if(game::$instance->getGameStateValue("variant_one_hex") != 0)
            {
                game::$instance->setGameStateValue("variant_imposed_hex", game::$instance->getGameStateValue("variant_one_hex"));

                game::$instance->notifyAllPlayers(
                'message',
                clienttranslate('${player_name} forces ${next} to move the last penguin in game'),
                array(
                    'next' =>    [   'log' => '<b style="color: #${color};">${opponent_name}</b>',
                                        'args'=> ['opponent_name' => $nextplayer_name, 'color'=>$nextplayer_color]
                                    ],

                    'player_name' => $this->player_name,
                    

                )
                );
            
            }
            else{
                game::$instance->setGameStateValue("variant_imposed_hex", 0);
            
            }

            game::$instance->setGameStateValue("variant_one_hex", 0);
            game::$instance->giveExtraTime($this->player_id);
            
            if(($count_blocked == $count_ping)||($count_isolate + $count_blocked != $count_ping))
            {
                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                
            }

            
            else
            {
                
                game::$instance->addPending($this->player_id, "Isolate");
            }

                        
        }
        
        
    }


    function argVariantConfirm($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('"Sleepy Penguins": ${actplayer} must impose a penguin on the next player');
        $ret['titleyou'] = clienttranslate('${you} must confirm');

        $ret["selected"][] = $parg1;
        


        $ret['buttons'][] = 'yes';
        $ret['buttons'][] = 'no';


        

        
        

        return $ret;
    }

    function VariantConfirm($parg1, $parg2, $varg1, $varg2)
    {
        $nextplayer = game::$instance->NextPlayerNoBlocked($this->player_id);

        
        if($varg1 == 'no')
        {
            game::$instance->addPending($this->player_id, "Variant");
        }

        if($varg1 == 'yes')
        {

            $nextplayer_name = self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id={$nextplayer}");
            $nextplayer_color = self::getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id={$nextplayer}");

            /// TEST ISOLATED PENGUIN

            $hex_occuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            $count_ping = count($hex_occuped);
            
            /*if (($index = array_search($newhex, $hex_occuped)) !== false) { // permet de mettre newhex en dernier
            unset($hex_occuped[$index]);                        // Supprime la valeur cible
            $hex_occuped = array_values($hex_occuped);          // Réindexe proprement le tableau
            array_push($hex_occuped, $newhex);                  // Ajoute la valeur cible à la fin
            }*/

            $count_isolate = 0;
            $count_blocked = 0;

            foreach($hex_occuped as $hex)
            {
                $tiles = game::$instance->testMovePenguin($this->player_id, $hex);
                if($tiles == null)
                {
                    $count_blocked = $count_blocked +1;
                }

                else
                {

                    $test_isolate = game::$instance->testIsolatedPenguin($hex);
                    if($test_isolate == true)
                    {
                        $count_isolate = $count_isolate +1;
                            
                                            
                    }
                }

            }


            
            $explode = explode('_', $parg1);
            $hex = intval($explode[1]);
            game::$instance->setGameStateValue("variant_imposed_hex", $hex);
            game::$instance->giveExtraTime($this->player_id);
            

            game::$instance->notifyAllPlayers(
                'message',
                clienttranslate('${player_name} forces ${next} to move a penguin'),
                array(
                    'next' =>    [   'log' => '<b style="color: #${color};">${opponent_name}</b>',
                                        'args'=> ['opponent_name' => $nextplayer_name, 'color'=>$nextplayer_color]
                                    ],

                    'player_name' => $this->player_name,
                    

                )
                );

            if(($count_blocked == $count_ping)||($count_isolate + $count_blocked != $count_ping))
            {
                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                
            }

            
            else
            {
                game::$instance->addPending($this->player_id, "NormalTurn");
            }

                        
        }
        
        
        
    }

    //// VARIANT PUSHING


    function argPushing($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        


        $ret["selected"][] = $parg1;

        $explode = explode('_', $parg1);
        $move = game::$instance->testMovePenguin($this->player_id, $explode[1]);
        $push = game::$instance->canPush($explode[1]);


        $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

        foreach ($hexoccuped as $hex) {
                        
            if ($hex != $explode[1])
            {
                $listhex = game::$instance->testMovePenguin($this->player_id, $hex);
            
                if (count($listhex)>=1)
                {
                    $ret["selectable2"][] = 'hex_' . $hex;
                }

                else{

                if(count(game::$instance->canPush($hex)) >=1)
                {
                    $ret["selectable2"][] = 'hex_' . $hex;
                }

                }

            }
        }

        
        
        if ((count($move)>=1)&&(count($push)>=1)&&(count($ret["selectable2"]) >= 1))
        {
          
            $ret['titleyou'] = clienttranslate('${you} must choose your action or change penguin');
            $ret['buttons'][] = 'move';
            $ret['buttons'][] = 'push';
            $ret['buttons'][] = 'cancel';
        }

        if ((count($move)>=1)&&(count($push)>=1)&&(count($ret["selectable2"]) == 0))
        {
             
            $ret['titleyou'] = clienttranslate('${you} must choose your action');
            $ret['buttons'][] = 'move';
            $ret['buttons'][] = 'push';
            $ret['buttons'][] = 'cancel';
        }

        
        

        

        return $ret;
    }

    function Pushing($parg1, $parg2, $varg1, $varg2)
    {
        if($varg1 == null)
        {
            $explode = explode('_', $parg1);
            $move = game::$instance->testMovePenguin($this->player_id, $explode[1]);
            $push = game::$instance->canPush($explode[1]);

            if (count($move)>=1)
            {
                game::$instance->addPending($this->player_id, "NormalTurn2", $parg1);
            }

            if (count($push)>=1)
            {
                game::$instance->addPending($this->player_id, "Pushing2", $parg1);
            }



        }
        elseif($varg1 == 'cancel')
        {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }

        elseif($varg1 == 'move')
        {
            game::$instance->addPending($this->player_id, "NormalTurn2", $parg1);
        }

        elseif($varg1 == 'push')
        {
            game::$instance->addPending($this->player_id, "Pushing2", $parg1);
        }

        else{
            game::$instance->addPending($this->player_id, "Pushing", $varg1);
        }

        
    }

    function argPushing2($parg1, $parg2)
    {
        $ret = array();
        $ret["selectable"] = array();
        $ret["selectable2"] = array();
        $ret["selected"] = array();
        $ret['buttons'] = array();
        $ret['title'] = clienttranslate('${actplayer} must move a penguin');
        $ret['titleyou'] = clienttranslate('${you} must choose which penguin to push');

        

        $ret["selected"][] = $parg1;
        $ret['buttons'][] = 'cancel';

        $explode = explode('_', $parg1);

        $push = game::$instance->canPush($explode[1]);
        foreach ($push as $hex)
        {
            $ret["selectable"][] = 'hex_' . $hex;

            
        }

        return $ret;
    }

    function Pushing2($parg1, $parg2, $varg1, $varg2)
    {

        if($varg1 == 'cancel')
        {
            game::$instance->addPending($this->player_id, "NormalTurn");
        }
        else{

            $test = 0;
            $explode = explode('_', $varg1);
            $explode2 = explode('_', $parg1);

            $starthex = intval($explode2[1]);
            $newhex = intval($explode[1]);

           

            $hexoccuped = self::getObjectListFromDB("SELECT hex FROM penguin WHERE player_id = '{$this->player_id}' AND hex != 0", true);

            if (in_array($explode[1], $hexoccuped)) {

                $test = 1;
                
            }

            if ($test == 1)
            {    
                
                game::$instance->addPending($this->player_id, "Pushing", $varg1);
                     
                
            }

            if ($test == 0)
            {   
                $penguin_info_opponent = self::getObjectListFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$explode[1]}'");
                $id_opponent = self::getUniqueValueFromDB("SELECT player_id FROM penguin WHERE hex ='{$explode[1]}'");
                self::DbQuery("UPDATE penguin set hex = 0 WHERE player_id = '{$id_opponent}' AND hex ='{$explode[1]}'");

                game::$instance->notifyAllPlayers(
                'removePenguins',
                '',
                array(
                    'penguin_infos' => $penguin_info_opponent,
                    'last_tile' => 0,

                )
                ); 

                self::DbQuery("UPDATE penguin set hex = $newhex WHERE player_id = '{$this->player_id}' AND hex = '{$starthex}'");
                self::DbQuery("UPDATE player set player_new_tile = '{$newhex}' WHERE player_id = '{$this->player_id}'");
                

                $penguin_info = self::getObjectFromDB("SELECT id, player_id, no, hex FROM penguin WHERE hex ='{$explode[1]}'");

                game::$instance->notifyAllPlayers(
                'movePenguin',
                '',
                array(
                    'player_name' => $this->player_name,
                    'starthex' => $starthex,
                    'penguin_infos' => $penguin_info,
                    'pushing' => 1,

                )
                );

                game::$instance->giveExtraTime($this->player_id);
                game::$instance->updateNbTurns(1);
                game::$instance->addPendingFirst($this->player_id, "NormalTurn");
                     
                
            }

        }
       
        
    }






}
