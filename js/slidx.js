//--------------INFO----------------//
// Desarrollador: Iván Barcia
// Sitio Web: http://ivanbarcia.eu
// Hecho en: Galicia, España

// Nombre: Slidx
// Versión: 2.4
// Sitio Web: https://github.com/ivarcia/codelab-slidx
//----------------------------------//


$(document).ready(function(){

    //----------  CONFIGURACIÓN  -----------//
    var button       = '#slidx-button'; //Elemento en el que pulsamos para abrir y cerrar el menú.
    var menu         = '#slidx-menu'; //Elemento que contiene el menú responsive.
    var mode         = 'click' //Escribe 'click' o 'hover' si quieres que se abra en menú al pulsar el botón o al pasar por encima de él.
    var side         = 'right' //Indica de que lado está el menú ('right' o 'left')
    var shadow       = 'no' //Indica si se crea una sombra en el resto de la página, cuando se abre el menú ('yes' o 'no')
    var opacity      = 0.6; //Opacidad de la sombra que se crea en el resto de la página con el menú abierto. (0=transparente 1=opaco)
    var size         = 350; //Ancho del menú.
    var speed        = 0.5; //Velocidad de apertura y cierre (en s.)
    var normalTime   = 0; //Tiempo que tarda el menú en abrirse/cerrarse cuando pulsamos el botón (en ms. recomendable dejar en 0).
    var menuTime     = 300; //Tiempo que tarda el menú en cerrarse cuando pulsamos un elemento dentro del menu (en ms.).

    var speedM = speed * 1000;
    
    
    //----------  ESTILOS CSS  -----------//
    //Añadimos los  estilos básicos por defecto al menú.
    $(menu).css({
        'position'   : 'fixed',
        'top'        : '0px',
        'width'      : size + 'px',
        'max-width'  : '100%',
        'height'     : '100%',
        'overflow-y' : 'auto',
        'transition' : speed + 's',
        'z-index'    : 98,
	//'opacity'    : '0.7',
    });
        
        //Si es derecho
        if (side == 'right') {
            $(menu).css({'right': '-' + size + 'px',})
        }
        
        //Si es izquierdo
        if (side !== 'right') {
            $(menu).css({'left': '-' + size + 'px',})
        }
    
    //Añadimos los estilos básicos por defecto al botón.
    $(button).css({
        'position'   : 'fixed',
        'top'        : '0px',
        'transition' : speed + 's',
        'z-index'    : 97,
    });
    
        if (side == 'right') {
            $(button).css({'right': '0px',})
        }
    
        if (side !== 'right') {
            $(button).css({'left': '0px',})
        }
    
    
    //----------  FUNCIONES  -----------//
    //Ésta es la función que abre el menú.
    function open(){
      
        if (side == 'right') {
            
            $(menu).animate({
                right: '0',
            }, normalTime );

            if (mode == 'click') {
                $(button).animate({
                    right: size,
                }, normalTime );
            }
            
        }

        if (side !== 'right') {
            
            $(menu).animate({
                left: '0',
            }, normalTime );

            if (mode == 'click') {
                $(button).animate({
                    left: size,
                }, normalTime );
            }
        }

        $(menu).addClass('slidx-open');

        if (shadow == 'yes') {
            $("<div>",{
            id: "slidx-shadow", //atributo directo, igual que si fuéramos con attr(“id”)
            css: //propiedad de jQuery
                {
                "position": "fixed",
                "top": "0px",
                "width": "100%",
                "height": "100%",
                "background-color": "#000000",
                "opacity": "0",
                "z-index": "96",
                },
            }).appendTo('html');

            $('#slidx-shadow').fadeTo(speedM, opacity);
        }
    };
    
    //Ésta es la función que cierra el menú. (Hay dos versiones en función del tiempo de cierre)
    function close(delayTime){
        if (side == 'right') {
            $(menu).animate({
                right: '-' + size,
            }, delayTime)

            if (mode == 'click') {
                $(button).animate({
                    right: 0,
                }, delayTime);
            }
        }
        
        if (side !== 'right') {
            $(menu).animate({
                left: '-' + size,
            }, delayTime)

            if (mode == 'click') {
                $(button).animate({
                    left: 0,
                }, delayTime);
            }
        }

        $(menu).removeClass('slidx-open');
        $('#slidx-shadow').fadeOut(speedM);
    };
    
    //----------  ACTIVADORES  -----------//
    //----- Modo CLICK -----//
    if (mode == 'click') {
        // Al pulsar el button abrimos el menú si está cerrado, o lo cerramos si está abierto.
        $(button).click(function() {
            if (!$(menu).hasClass('slidx-open')) {
                open();
            }
            else {
                close(normalTime);
            } 
        }); 

        //Al pulsar en un elemento del menú, también se cierra el menu.
        //Fíjate que el tiempo de cierre que introduzco es mayor que cuando lo cierro con el boton directamente, simplemente porque queda mejor visualmente
/*        $(menu).click(function() {
            close(menuTime);
        });
*/
    }
    
    $(document).on('click', '#slidx-shadow', function() {
        close(normalTime);
    });
    
    
    //----- Modo HOVER -----//
    if (mode == 'hover') {
        // Al pasar el ratón por encima del botón abrimos el menú si está cerrado, o lo cerramos si está abierto.
        $(button).mouseover(function() {
            if (!$(menu).hasClass('slidx-open')) {
                open();
            }
            else {
                close(normalTime);
            } 
        });

        //Al sacar el ratón del menú, se cierra en menú.
        $(menu).mouseleave(function() {
            close(normalTime);
        });
        
        //Al pulsar en un elemento del menú, también se cierra el menu.
       //fíjate que el tiempo de cierre que introduzco es mayor que cuando lo cierro con el boton directamente, simplemente porque queda mejor visualmente
        $(menu).click(function() {
            close(menuTime);
        });
    };
});
