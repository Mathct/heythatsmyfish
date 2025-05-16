/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * heythatsmyfish implementation : © <Mathieu Chatrain> <mathieu.chatrain@gmail.com> && <Yannick Priol> <camertwo@hotmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * heythatsmyfish.js
 *
 * heythatsmyfish user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */


//Tisaac way to debug ;)
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    g_gamethemeurl + 'modules/js/Core/game.js'
],
function (dojo, declare) {

    const TABLE_WIDTH = 880;
    const TABLE_HEIGHT = 796;
    const TOOLTIP_DELAY = 500;



    return declare("bgagame.heythatsmyfish", [customgame.game], {
        constructor: function(){
            console.log('heythatsmyfish constructor');

        },
        

        
/////////////////////////////////////////////////////////////////////////////////           
//    _____                      _____        _            
//   / ____|                    |  __ \      | |           
//  | |  __  __ _ _ __ ___   ___| |  | | __ _| |_ __ _ ___ 
//  | | |_ |/ _` | '_ ` _ \ / _ \ |  | |/ _` | __/ _` / __|
//  | |__| | (_| | | | | | |  __/ |__| | (_| | || (_| \__ \
//   \_____|\__,_|_| |_| |_|\___|_____/ \__,_|\__\__,_|___/
//                                                        
/////////////////////////////////////////////////////////////////////////////////
        
setup: function( gamedatas )
{
    console.log( "Starting game setup" );


    this.players = gamedatas.players; // A RAJOUTER POUR MOTEUR (UTILITY METHODS)
    this.tiles = gamedatas.tiles;
    this.penguins = gamedatas.penguins;
    
    this.tiles_collected = this.gamedatas.tiles_collected;
    
    this.colors = ['ff6e48','fec307','9d56cc','32a094'];
    this.colorClasses = {'ff6e48': 'red', 'fec307': 'yellow', '9d56cc': 'purple','32a094': 'green'};
    
    this.tile_counter = {};


    this.setupPlayersBoard();
    this.setupBoard();
    this.addHelp();
    this.setupCounters();
    this.setupTooltips();
    

    // Setup game notifications to handle (see "setupNotifications" method below)
    this.setupNotifications();

    

    console.log( "Ending game setup" );
},

/////////////////////////////////////////////////////////////////////////////////   
//         _____ _        _            
//        / ____| |      | |           
//       | (___ | |_ __ _| |_ ___  ___ 
//        \___ \| __/ _` | __/ _ \/ __|
//        ____) | || (_| | ||  __/\__ \
//       |_____/ \__\__,_|\__\___||___/
//                                    
/////////////////////////////////////////////////////////////////////////////////    


///////////////////////////////////////////////////
//// Game & client states

// onEnteringState: this method is called each time we are entering into a new game state.
//                  You can use this method to perform some user interface changes at this moment.
//
onEnteringState: function( stateName, args )
{
    if( stateName != 'pending') {
        console.log('Entering state: '+stateName, args);
    }

      
    
    switch( stateName )
    {

        case 'playerTurn':
            this.args = args.args;

            this.removeBackPenguin(this.getActivePlayerId());

            if(this.isCurrentPlayerActive()) {
                this.args.selectable.forEach(sid => {

                    dojo.addClass(sid,"selectable");

                    if(this.gamedatas.players[this.getActivePlayerId()].color == 'ff6e48')
                    {
                        dojo.addClass(sid,"selectable_ff6e48");
                    }
                    if(this.gamedatas.players[this.getActivePlayerId()].color == 'fec307')
                    {
                        dojo.addClass(sid,"selectable_fec307");
                    }
                    if(this.gamedatas.players[this.getActivePlayerId()].color == '9d56cc')
                    {
                        dojo.addClass(sid,"selectable_9d56cc");
                    }
                    if(this.gamedatas.players[this.getActivePlayerId()].color == '32a094')
                    {
                        dojo.addClass(sid,"selectable_32a094");
                    }
                    
                });

                this.args.selectable2.forEach(sid => {
                    dojo.addClass(sid,"selectable2");
                });


                this.args.selected.forEach(sid => {
                    dojo.addClass(sid,"selected");   
                });

                // add selectable2 and selected to selectable for further listeners.
                this.args.selectable.push(...this.args.selectable2);
                //this.args.selectable.push(...this.args.selected);

                this.setupConnections(this.args.selectable);

                if(args.args.titleyou != null)
                {
                    $('pagemaintitletext').innerHTML = this.format_string_recursive(_(args.args.titleyou).replace('${you}', this.divYou()).replace(/#opponent#/g,args.args.opponent).replace('#nb#',args.args.nb).replace('#nb2#',args.args.nb2).replace('#icon#',args.args.icon).replace('#icon2#',args.args.icon2), args.args);
                }
            
            }
            else{
                if(args.args.title != null)
                {
                    $('pagemaintitletext').innerHTML = this.format_string_recursive(_(args.args.title).replace('${actplayer}', this.divActPlayer()).replace('#nb#',args.args.nb).replace('#nb2#',args.args.nb2).replace('#icon#',args.args.icon).replace('#icon2#',args.args.icon2), args.args);  
                }
            }
            break;

    
    
        case 'dummmy':
            break;
    }
},

// onLeavingState: this method is called each time we are leaving a game state.
//                 You can use this method to perform some user interface changes at this moment.
//
onLeavingState: function( stateName )
{
    console.log( 'Leaving state: '+stateName );

    dojo.query(".selectable").removeClass("selectable");
    dojo.query(".selectable_ff6e48").removeClass("selectable_ff6e48");
    dojo.query(".selectable_fec307").removeClass("selectable_fec307");
    dojo.query(".selectable_9d56cc").removeClass("selectable_9d56cc");
    dojo.query(".selectable_32a094").removeClass("selectable_32a094");
    dojo.query(".selectable2").removeClass("selectable2");
    dojo.query(".selected").removeClass("selected");
    
    switch( stateName )
    {
    
        case 'playerTurn':
            this.removeConnections();            
            break;     
    
        case 'dummy':
            break;
    }               
}, 

// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
//                        action status bar (ie: the HTML links in the status bar).
//        
onUpdateActionButtons: function( stateName, args )
{
    console.log( 'onUpdateActionButtons: '+stateName, args );
              
    if( this.isCurrentPlayerActive() )
    {            
        switch( stateName )
        {
            case "playerTurn":
                for( var nb in args.buttons )
                { 
                    if(args.buttons[nb] == "cancel")
                    {
                        this.addActionButton( 'cancel', _("Cancel") ,'onOpButton', null, null, 'red' );
                    }
                    if(args.buttons[nb] == "pass")
                    {
                        this.addActionButton( 'pass', _("Pass") ,'onOpButton', null, null, 'red' );
                    }
                    if(args.buttons[nb] == "yes")
                    {
                        this.addActionButton( 'yes', _("Yes") ,'onOpButton', null, null, 'blue' );
                    }
                    if(args.buttons[nb] == "no")
                    {
                        this.addActionButton( 'no', _("No") ,'onOpButton', null, null, 'red' );
                    }
                }
                break;
        }
    }
},        

/////////////////////////////////////////////////////////////////////////////////         
//   _    _ _   _ _ _ _                          _   _               _     
//  | |  | | | (_) (_) |                        | | | |             | |    
//  | |  | | |_ _| |_| |_ _   _   _ __ ___   ___| |_| |__   ___   __| |___ 
//  | |  | | __| | | | __| | | | | '_ ` _ \ / _ \ __| '_ \ / _ \ / _` / __|
//  | |__| | |_| | | | |_| |_| | | | | | | |  __/ |_| | | | (_) | (_| \__ \
//   \____/ \__|_|_|_|\__|\__, | |_| |_| |_|\___|\__|_| |_|\___/ \__,_|___/
//                         __/ |                                           
//                        |___/                                            
/////////////////////////////////////////////////////////////////////////////////  

divYou : function() {
    var color = this.players[this.player_id].color;
    var color_bg = "";
    var you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + _("You") + "</span>";
    return you;
},

divActPlayer : function() {        	
    var color = this.players[this.getActivePlayerId()].color;
    var name = this.players[this.getActivePlayerId()].name;
    var color_bg = "";
    var you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + name + "</span>";
    return you;
},

format_string_recursive : function(log, args) {
    try {
        if (log && args && !args.processed) {
            args.processed = true;
           
        }
    } catch (e) {
        console.error(log,args,"Exception thrown", e.stack);
    }
    return this.inherited(arguments);
},

/*************************************************
 * 
 *  setup connections from this.args.selectable
 * on each beginning of new State (Player Turn)
 * 
 ************************************************/

setupConnections: function(selectables) {
    this.connections = [];

    selectables.forEach(elt_id => {
        const element = document.getElementById(elt_id);

        const resourceClickHandler = (evt) => this.onSelect(evt);
        element.addEventListener('click', resourceClickHandler);
        this.connections.push({ element, event: 'click', handler: resourceClickHandler });
    });

},


/*************************************************
 * 
 *  reset all connections 
 *  on leaving a State
 * 
 ************************************************/

removeConnections: function() {
    this.connections.forEach(connection => {
        const { element, event, handler } = connection;
        element.removeEventListener(event, handler);
    });
    this.connections = [];
},


setupPlayersBoard: function() {
    console.log('Setting up the players board');

    Object.values(this.players).forEach((player) => {
        const playerBoardElement = document.getElementById("player_board_" + player.id);
        playerBoardElement.insertAdjacentHTML("beforeend", `<div class="a_board" id="ai_board_${player.id}"></div>`);

        const aiBoard = document.getElementById("ai_board_" + player.id);

         const fish_type = player.last_tile;
        let tile_x;
        let tile_y;
        if( fish_type == '0' ) {
            tile_x = 0;
            tile_y = 0;
        }
        else {
            const parts = fish_type.split("_");
            tile_x = parseInt(parts[0]);
            tile_y = parseInt(parts[1]) - 1;
        }


        const fishGroup = `
            <div class="icon-group">
                <div class="icons_fish" id="icon_fish_${player.id}" style="background-position: -${tile_x}00% -${tile_y}00%;"></div>
            </div>
        `;
        aiBoard.insertAdjacentHTML("beforeend", fishGroup);




        const tileGroup = `
            <div class="icon-group">
                <div class="icons ice" id="icon_tile_${player.id}"></div>
                <span id="tile_counter_${player.id}" class="tmf_icon_text">${player.tile}</span>
            </div>
        `;
        aiBoard.insertAdjacentHTML("beforeend", tileGroup);

        aiBoard.insertAdjacentHTML("beforeend", `<div class="icon-group" id="penguins_group_${player.id}"></div>`);



    });

    this.penguins.reverse().forEach((penguin) => {
        if (penguin.hex == '0') {
            // Récupération de la couleur du joueur
            const player = this.players[penguin.player_id];
            const playerColor = player.color; 
            const colorClass = this.colorClasses[playerColor];

            // Sélection du bon groupe des pingouins
            const penguinGroup = document.getElementById(`penguins_group_${penguin.player_id}`);

            const penguinIcon = `
                <div class="icon_penguins front ${colorClass}" id="icn_peng_${penguin.player_id}_${penguin.no}"></div>
            `;
            penguinGroup.insertAdjacentHTML("beforeend", penguinIcon);

        }
    });
},


setupBoard: function () {
    console.log('Setting up the board');

    const board = document.getElementById("board_id");

    // creation of hexes for selections

    const rows = [7, 8, 7, 8, 7, 8, 7, 8]; // Number of haxoagos per row
    const hexWidth = 108.25; 
    const hexHeight = 93.75;
    const spacing = 2; // gap between hexagons

    rows.forEach((cols, row) => {
        let xOffset = cols === 7 ? (hexWidth + spacing) / 2 : 0;

        for (let col = 0; col < cols; col++) {
            const hex = document.createElement("div");
            hex.classList.add("hex");
            hex.id = `hex_${row * 10 + col + 1}`;

            // Positionnement horizontal en pixels
            const x = col * (hexWidth + spacing) + xOffset;

            // Positionnement vertical en pixels avec un chevauchement de 75%
            const y = row * (hexHeight * 1 + spacing);

            hex.style.left = `${x}px`;
            hex.style.top = `${y}px`;
            board.appendChild(hex);
        }
    });


    // creation of tiles for sprites

    this.tiles.forEach((tile) => {

        if( tile.location == 'board') {
        
            const tile_elt = document.createElement("div");
            tile_elt.classList.add("tile");
            tile_elt.id = `tile_${tile.location_arg}`;

            let backgroundX = 0;
            let backgroundY = 0;
            if( tile.location =='board') {
                backgroundX = parseInt(tile.type) * -100;
                backgroundY = (parseInt(tile.type_arg)-1) * -100;
            }
            tile_elt.style.backgroundPosition = `${backgroundX}% ${backgroundY}%`;

            const tile_pos = tile.location_arg -1;

            const row =  Math.floor(tile_pos/10);
            const col = tile_pos % 10;

            let xOffset = row % 2 == 0 ? (hexWidth + spacing) / 2 : 0;
            // Positionnement horizontal en pixels
            const x = col * (hexWidth + spacing) + xOffset;

            // Positionnement vertical en pixels avec un chevauchement de 75%
            const y = row * (hexHeight * 1 + spacing);

            tile_elt.style.left = `${x}px`;
            tile_elt.style.top = `${y}px`;   
            
            
            // Génère un mouvement aléatoire entre -2px et 2px pour x et y
            const xMove = (Math.random() * 4 - 2).toFixed(1) + "px";
            const yMove = (Math.random() * 4 - 2).toFixed(1) + "px";

            // Applique des variables CSS pour chaque tuile
            tile_elt.style.setProperty("--x-move", xMove);
            tile_elt.style.setProperty("--y-move", yMove);

            // Ajoute l'animation CSS

            if( this.getGameUserPreference('101') == 1 ) {
                tile_elt.style.animation = `floatTile ${5 + Math.random() * 2}s infinite ease-in-out`;

            }
            



            board.appendChild(tile_elt);
        
        }

    });
    
    this.penguins.forEach((penguin) => {
        if (penguin.hex > 0) {
            this.createPenguin(penguin);
        }
    });

    //ANIMATION ICEBERGS

    // Génère un mouvement aléatoire pour x et y
    const xMove = (Math.random() * 4 - 2).toFixed(1) + "px";
    const yMove = (Math.random() * 4 - 2).toFixed(1) + "px";

    // Applique des variables CSS pour chaque tuile
    const iceberg = document.getElementById(`iceberg`);
    iceberg.style.setProperty("--x-move", xMove);
    iceberg.style.setProperty("--y-move", yMove);
    const iceberg2 = document.getElementById(`iceberg2`);
    iceberg2.style.setProperty("--x-move", xMove);
    iceberg2.style.setProperty("--y-move", yMove);

    if( this.getGameUserPreference('101') == 1 ) {
        
        iceberg.style.animation = `floatTile ${3 + Math.random()}s infinite ease-in-out`;
        iceberg2.style.animation = `floatTile ${3 + Math.random()}s infinite ease-in-out`;
    }
 
},




addHelp: function() {
    const isTouch = document.getElementById('ebd-body').classList.contains('touch-device');

    const helpButton = document.createElement('div');
    helpButton.id = 'tmf_help';
    helpButton.className = 'tmf-help-button';
    helpButton.textContent = '?';

    document.body.appendChild(helpButton);

    if (isTouch) {
        helpButton.addEventListener('click', () => {
            const penguins = document.querySelectorAll('.penguins');
            const isHidden = penguins.length && penguins[0].classList.contains('hidden');
            penguins.forEach(el => el.classList.toggle('hidden', !isHidden));
        });
    } else {
        helpButton.addEventListener('mouseenter', () => {
            document.querySelectorAll('.penguins').forEach(el => el.classList.add('hidden'));
        });

        helpButton.addEventListener('mouseleave', () => {
            document.querySelectorAll('.penguins').forEach(el => el.classList.remove('hidden'));
        });
    }
},



removeBackPenguin: function( player_id) {
    const penguins = document.querySelectorAll(`[id^="peng_${player_id}"]`);

    penguins.forEach(penguin => {
        if (penguin.classList.contains('back')) {
            penguin.classList.replace('back', 'front');
        }
    });
},


setupCounters: function() {

    console.log( 'counters');
    
    Object.values(this.players).forEach(player => {
        // Compteur pour les tiles
        this.tile_counter[player.id] = new ebg.counter();
        this.tile_counter[player.id].create('tile_counter_' + player.id);
        const tile_value = isNaN(this.tiles_collected[player.id]) ? 0 : this.tiles_collected[player.id];
        this.tile_counter[player.id].toValue(tile_value);
    });

},

setupTooltips:function () {
    this.addTooltipHtmlToClass('icons_fish', _('Last tile collected'), TOOLTIP_DELAY);
    this.addTooltipHtmlToClass('ice', _('Tiles collected'), TOOLTIP_DELAY);
},

onScreenWidthChange: function () {
    this.updateLayout();
},

updateLayout: function() {
    var gameWidth = TABLE_WIDTH;
    var gameHeight = TABLE_HEIGHT;

    var horizontalScale = document.getElementById('game_play_area').clientWidth / gameWidth;
    var verticalScale = (window.innerHeight - 0) / gameHeight;

    var scale = Math.min(1, horizontalScale, verticalScale);

    var resized_div = document.getElementById('resized_id');
    var play_area_height = dojo.marginBox("board_id").h;

    resized_div.style.transform = scale === 1 ? '' : "scale(".concat(scale, ")");

    dojo.style("resized_id",'height', (play_area_height*scale)+'px');

},

createPenguin: async function(penguin) {
    // get color class from player_id
    const player = this.players[penguin.player_id];
    const playerColor = player.color; 
    const colorClass = this.colorClasses[playerColor];

    const view = penguin.hex == player.new_tile ? "back" : "front"; // A MODIFIER
    
    // penguin token creation
    const penguinToken = document.createElement("div");
    penguinToken.classList.add("penguins", colorClass, view);
    penguinToken.id = `peng_${penguin.player_id}_${penguin.no}`;

    // penguin is added to tile
    const hexTile = document.getElementById(`tile_${penguin.hex}`);
    hexTile.appendChild(penguinToken);

    await new Promise(resolve => {
    if( this.getGameUserPreference('101') == 1 ) {
        hexTile.style.animationIterationCount = '1'; // infinite animation back
    }
        penguinToken.classList.add('zoom-in-animation');
        penguinToken.addEventListener('animationend', () => {
            penguinToken.classList.remove('zoom-in-animation'); // Supprime l'animation une fois terminée
            if( this.getGameUserPreference('101') == 1 ) {
                hexTile.style.animationIterationCount = 'infinite'; // infinite animation back
            }


            resolve();
        }, { once: true });
    });
},

/////////////////////////////////////////////////////////////////////////////////  
//         _____  _                       _                  _   _             
//        |  __ \| |                     ( )                | | (_)            
//        | |__) | | __ _ _   _  ___ _ __|/ ___    __ _  ___| |_ _  ___  _ __  
//        |  ___/| |/ _` | | | |/ _ \ '__| / __|  / _` |/ __| __| |/ _ \| '_ \ 
//        | |    | | (_| | |_| |  __/ |    \__ \ | (_| | (__| |_| | (_) | | | |
//        |_|    |_|\__,_|\__, |\___|_|    |___/  \__,_|\___|\__|_|\___/|_| |_|
//                         __/ |                                               
//                        |___/                                                
/////////////////////////////////////////////////////////////////////////////////  

stopEvent:function (evt) {
    if (evt) {
        evt.preventDefault();
        evt.stopPropagation();
    }
},
        
onSelect: function(evt)
{        	 
    // Preventing default browser reaction
     this.stopEvent( evt );

    
    
    if(this.isCurrentPlayerActive() 
    && (evt.currentTarget.classList.contains('selectable')
        || evt.currentTarget.classList.contains('selected') 
        || evt.currentTarget.classList.contains('selectable2')))
    {
       
        this.bgaPerformAction('actSelect', { arg1: evt.currentTarget.id });
        
    }
},

onOpButton: function(evt)
{
    
    // Preventing default browser reaction
    this.stopEvent( evt );
    
    this.bgaPerformAction('actButton', { arg1: evt.currentTarget.id });
    
    

},


///////////////////////////////////////////////////////////////////////////////// 
//       _   _       _   _  __ _           _   _                 
//      | \ | |     | | (_)/ _(_)         | | (_)                
//      |  \| | ___ | |_ _| |_ _  ___ __ _| |_ _  ___  _ __  ___ 
//      | . ` |/ _ \| __| |  _| |/ __/ _` | __| |/ _ \| '_ \/ __|
//      | |\  | (_) | |_| | | | | (_| (_| | |_| | (_) | | | \__ \
//      |_| \_|\___/ \__|_|_| |_|\___\__,_|\__|_|\___/|_| |_|___/
//                                                                 
/////////////////////////////////////////////////////////////////////////////////  


    notif_placePenguin: async function(args) {
        debug('notif_placePenguin: player places a Penguin', args);

        const penguin = args.penguin_infos;
        console.log( 'penguin', penguin);

        // this.penguins is updated
        const penguinToUpdate = this.penguins.find(p => p.id === penguin.id);
        penguinToUpdate.hex = penguin.hex; // Met à jour la valeur de 'hex'
        

        // a penguin icon is removed
        const icon_to_remove_id = `icn_peng_${penguin.player_id}_${penguin.no}`;
        const iconToRemove = document.getElementById(icon_to_remove_id);
        
        await new Promise(resolve => {
            iconToRemove.classList.add('zoom-out-animation');
            iconToRemove.addEventListener('animationend', resolve, { once: true });
        });
        this.destroy(iconToRemove);

        dojo.query(".selectable").removeClass("selectable");
        dojo.query(".selectable_ff6e48").removeClass("selectable_ff6e48");
        dojo.query(".selectable_fec307").removeClass("selectable_fec307");
        dojo.query(".selectable_9d56cc").removeClass("selectable_9d56cc");
        dojo.query(".selectable_32a094").removeClass("selectable_32a094");
        dojo.query(".selectable2").removeClass("selectable2");
        dojo.query(".selected").removeClass("selected");

        
        // a Penguin appears on the board, animated
        this.createPenguin(penguin);

    },

    notif_movePenguin: async function(args) {
        debug('notif_movePenguin: player moves a Penguin', args);

        const penguin = args.penguin_infos;
        // this.penguins is updated
        const penguinToUpdate = this.penguins.find(p => p.id === penguin.id);

        const old_hex = penguinToUpdate.hex;
        penguinToUpdate.hex = penguin.hex; // Met à jour la valeur de 'hex'

        const peng_id = `peng_${penguin.player_id}_${penguin.no}`;
        const hexTile = document.getElementById(`tile_${penguin.hex}`);

        dojo.query(".selectable").removeClass("selectable");
        dojo.query(".selectable_ff6e48").removeClass("selectable_ff6e48");
        dojo.query(".selectable_fec307").removeClass("selectable_fec307");
        dojo.query(".selectable_9d56cc").removeClass("selectable_9d56cc");
        dojo.query(".selectable_32a094").removeClass("selectable_32a094");
        dojo.query(".selectable2").removeClass("selectable2");
        dojo.query(".selected").removeClass("selected");

        const peng_line = Math.floor(penguin.hex / 10);
        let orient;
        if( peng_line % 2 == 1) {
            orient = ( old_hex % 10 < penguin.hex % 10) ? "right" : "left";
        }
        else {
            orient = ( old_hex % 10 <= penguin.hex % 10) ? "right" : "left";
        }

        const penguinToMove = document.getElementById(`peng_${penguin.player_id}_${penguin.no}`);
        penguinToMove.classList.replace('front', orient);
        

        await this.slide(peng_id, hexTile, {phantom: false});

        const tileToRemove = document.getElementById(`tile_${args.starthex}`);

        await new Promise(resolve => {
            tileToRemove.classList.add('zoom-out-animation');
        
            // Si une animation infinie est active, on la limite à une seule itération pour la sortie
            if (this.getGameUserPreference('101') == 1) {
                tileToRemove.style.animationIterationCount = '1';
            }
        
            tileToRemove.addEventListener('animationend', () => {
                tileToRemove.classList.remove('zoom-out-animation');
                this.destroy(tileToRemove);
                penguinToMove.classList.replace(orient, 'back');
                resolve();
            }, { once: true });
        });

        //ATTTENTION A ENLEVER POUR REMETTRE ANIMATION
       // this.destroy(tileToRemove);
        penguinToMove.classList.replace(orient, 'back');

        // this.penguins is updated

        const tileToUpdate = this.tiles.find(p => p.location_arg == args.starthex);
        tileToUpdate.location = penguin.player_id;
        tileToUpdate.location_arg = 0; // Met à jour la valeur de 'hex'
       
        const parts = args.last_tile.split("_");
        tile_x = parseInt(parts[0]);
        tile_y = parseInt(parts[1]) - 1;

        let iconFish = document.getElementById(`icon_fish_${penguin.player_id}`);
        iconFish.style.backgroundPosition = `-${tile_x}00% -${tile_y}00%`;


        this.tile_counter[penguin.player_id].incValue(1);
        this.tiles_collected[penguin.player_id] += 1;




    },


    notif_removePenguins: async function(args) {
        debug('notif_removePenguin: a player cannot move and all Penguins & Tiles are removed', args);

        const penguins = args.penguin_infos;

        for (let penguin of penguins) {

            // get player's color
            const player = this.players[penguin.player_id];
            const playerColor = player.color;
            const colorClass = this.colorClasses[playerColor];

            // Add an icon in players'panel
            const penguinGroup = document.getElementById(`penguins_group_${penguin.player_id}`);
            const penguinIcon = `
                <div class="icon_penguins ${colorClass}" id="icn_peng_${penguin.player_id}_${penguin.no}"></div>
            `;
            penguinGroup.insertAdjacentHTML("beforeend", penguinIcon);

            // remove penguin from board
            const penguinElement = document.getElementById(`peng_${penguin.player_id}_${penguin.no}`);

            await new Promise(resolve => {
                penguinElement.classList.add('zoom-out-animation');
                penguinElement.addEventListener('animationend', () => {
                    this.destroy(penguinElement);
                    resolve();
                }, { once: true });
            });
            

            // remove Tile
            const tileToRemove = document.getElementById(`tile_${penguin.hex}`);

            await new Promise(resolve => {
                tileToRemove.classList.add('zoom-out-animation');
                if( this.getGameUserPreference('101') == 1 ) {
                    tileToRemove.style.animationIterationCount = '1'; // to remove infinite animation
                }
                tileToRemove.addEventListener('animationend', () => {
                    tileToRemove.classList.remove('zoom-out-animation');
                    if( this.getGameUserPreference('101') == 1 ) {
                        tileToRemove.style.animationIterationCount = 'infinite'; // infinite animation back
                    }
                    this.destroy(tileToRemove);
                    //tileToRemove.style.backgroundPosition = `0% 0%`; // Réinitialisation

                    resolve();
                }, { once: true });
            });
            

            //counters update
            const tileToUpdate = this.tiles.find(t => t.location_arg == penguin.hex);

            this.tile_counter[penguin.player_id].incValue(1);
            this.tiles_collected[penguin.player_id] += 1;

            const parts = args.last_tile.split("_");
            tile_x = parseInt(parts[0]);
            tile_y = parseInt(parts[1]) - 1;

            let iconFish = document.getElementById(`icon_fish_${penguin.player_id}`);
            iconFish.style.backgroundPosition = `-${tile_x}00% -${tile_y}00%`;

        }
    },

    notif_score: async function( args ){
        debug('notif_score: final scores', args);
        
        this.scoreCtrl[args.player_id].toValue(args.total_fish);
    },


/*******************************
 ****** UTILS TISAAC *******
 ******************************/


/*******************************
 ****** HELP MODE TISAAC *******
    ******************************/
/**
 * Toggle help mode
 */
toggleHelpMode(b) {
    if (b) 
        this.activateHelpMode();
    else 
        this.desactivateHelpMode();
},

activateHelpMode() {
    this._helpMode = true;
    dojo.addClass('ebd-body', 'help-mode');
    this._displayedTooltip = null;
    document.body.addEventListener('click', this.closeCurrentTooltip.bind(this));
},

desactivateHelpMode() {
    this.closeCurrentTooltip();
    this._helpMode = false;
    dojo.removeClass('ebd-body', 'help-mode');
    document.body.removeEventListener('click', this.closeCurrentTooltip.bind(this));
},

closeCurrentTooltip() {
    if (!this._helpMode) 
        return;
    if (this._displayedTooltip == null) 
        return;
    else {
        this._displayedTooltip.close();
        this._displayedTooltip = null;
    }
},

    /*
    * Custom connect that keep track of all the connections
    *  and wrap clicks to make it work with help mode
    */
connect(node, action, callback) {
    this._connections.push(dojo.connect($(node), action, callback));
},

onClick(node, callback, temporary = true) {
    let safeCallback = (evt) => {
        evt.stopPropagation();
        if (this.isInterfaceLocked()) 
            return false;
        if (this._helpMode) 
            return false;
        callback(evt);
    };

    if (temporary) {
        this.connect($(node), 'click', safeCallback);
        dojo.removeClass(node, 'unselectable');
        dojo.addClass(node, 'selectable');
        this._selectableNodes.push(node);
    } else {
        dojo.connect($(node), 'click', safeCallback);
    }
},

    /**
     * Tooltip to work with help mode
     */


addCustomTooltip(id, html, config = {}) {
    config = Object.assign(
        {
            delay: 400,
            midSize: true,
            forceRecreate: false,
        },
        config,
    );

    let isMobile = window.matchMedia('(pointer: coarse)').matches;
    let longPressTimer = null;

    let getContent = () => {
        let content = typeof html === 'function' ? html() : html;
        if (config.midSize) {
            content = '<div class="midSizeDialog">' + content + '</div>';
        }
        return content;
    };

    if (this.tooltips[id] && !config.forceRecreate) {
        this.tooltips[id].getContent = getContent;
        return;
    }

    let tooltip = new dijit.Tooltip({
        getContent,
        position: this.defaultTooltipPosition,
        showDelay: config.delay,
    });
    this.tooltips[id] = tooltip;
    dojo.addClass(id, 'tooltipable');

    // Empêcher l'affichage au simple clic sur mobile
    dojo.connect($(id), 'click', (evt) => {
        if (isMobile && !this._helpMode) {
            evt.stopPropagation();
            return; // Bloque l'affichage du tooltip sur mobile sauf en mode help
        }

        if (!this._helpMode) {
            tooltip.close();
        } else {
            evt.stopPropagation();

            if (tooltip.state === 'SHOWING') {
                this.closeCurrentTooltip();
            } else {
                this.closeCurrentTooltip();
                tooltip.open($(id));
                this._displayedTooltip = tooltip;
            }
        }
    });

    tooltip.showTimeout = null;

    // Gestion du long press sur mobile
    dojo.connect($(id), 'touchstart', (evt) => {
        if (isMobile) {
            longPressTimer = setTimeout(() => {
                tooltip.open($(id));
            }, 500); // 500ms = temps pour considérer un long press
        }
    });

    dojo.connect($(id), 'touchend', (evt) => {
        if (isMobile) {
            clearTimeout(longPressTimer);
        }
    });

    dojo.connect($(id), 'touchmove', (evt) => {
        if (isMobile) {
            clearTimeout(longPressTimer); // Annule le long press si l'utilisateur glisse son doigt
        }
    });

    // Gestion normale des tooltips sur PC
    dojo.connect($(id), 'mouseenter', (evt) => {
        evt.stopPropagation();

        if (!this._helpMode && !this._dragndropMode) {
            if (isMobile) return; // Bloque l'affichage des tooltips sur mobile hors help mode

            if (tooltip.showTimeout != null) 
                clearTimeout(tooltip.showTimeout);

            tooltip.showTimeout = setTimeout(() => {
                if ($(id)) 
                    tooltip.open($(id));
            }, config.delay);
        }
    });

    dojo.connect($(id), 'mouseleave', (evt) => {
        evt.stopPropagation();
        if (!this._helpMode && !this._dragndropMode) {
            tooltip.close();
            if (tooltip.showTimeout != null) 
                clearTimeout(tooltip.showTimeout);
        }
    });
},


destroyTooltip(elem) {
    if (this.tooltips[elem.id]) {
    clearTimeout(this.tooltips[elem.id].showTimeout);
    this.tooltips[elem.id].close();
    this.tooltips[elem.id].destroy();
    delete this.tooltips[elem.id];
    }
},

destroy(elem, delayRemove = false) {
    this.destroyTooltip(elem);
    this.empty(elem);
    if(!delayRemove) 
    elem.remove();
},

empty(container) {

    container = $(container);
    container.childNodes.forEach((node) => {
    //!! destroy node makes gap in LOOP because of removing them
    this.destroy(node,true);
    });
    container.childNodes.forEach((node) => {
    node.remove();
    });
    container.innerHTML = '';
},



});             
});