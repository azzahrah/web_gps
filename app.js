window.app = {}
app.init = function () {
    app.panel_right = document.getElementById('panelright');
    app.tabs_left = document.getElementById('tabsLeft');
    app.dockingLayout = document.getElementById('layout');
    app.dockingLayout.layout = [
        {
            type: 'LayoutGroup',
            orientation: 'horizontal',
            items: [
                {
                    type: 'LayoutGroup',
                    items: [
                        {
                            type: 'LayoutPanel',
                            id: 'tabPanelLeft',
                            label: 'Output Panel',
                            tabPosition: 'hidden',
                            tabCloseButtons: false,
                            items: [{
                                label: 'Output',
                                content: `<div class="panelleft">
                                            <div class="panellefttop">
                                                <smart-text-box>test</smart-text-box>
                                                <smart-button id="searchGps">Helo</smart-button>
                                            </div>
                                            <div class="panelleftcenter">hello</div>
                                            <div class="panelleftbottom"></div>                                    
                                        </div>
                                        `
                            }]
                        },
                        {
                            id: 'tabPanelRight',
                            type: 'LayoutGroup',
                            label: 'Leaflet Map',
                            orientation: 'horizontal',
                            tabResize: false,
                            items: [
                                {
                                    id: 'log',
                                    label: 'Map',
                                    headerPosition: 'none',
                                    content: '<div id="mapid">helooo</div>'
                                },
                                {
                                    id: 'log',
                                    label: 'Map',
                                    size: 300,
                                    headerPosition: 'none',
                                    // tabPosition: 'hidden',
                                    tabCloseButtons: true,

                                    items: [
                                        {
                                            label: 'Data GPS',
                                            content: 'Data GPS'
                                        },
                                        {
                                            label: 'Data Alarm',
                                            content: 'Data GPS'
                                        }
                                    ]
                                }
                            ]
                        }
                        // {
                        //     id:'tabPanelRight',
                        //     type: 'LayoutPanel',
                        //     label: 'Leaflet Map',
                        //     tabPosition:'hidden',
                        //     tabCloseButtons:false,
                        //     tabResize :false,
                        //     items: [
                        //         {
                        //             id: 'log',
                        //             label: 'Map',
                        //             headerPosition: 'none',
                        //             content: '<div id="mapid">helooo</div>'
                        //         }
                        //     ]
                        // }
                    ],
                    orientation: 'vertical'
                }
            ]
        }
    ];

}
app.toggle_panel_right = function () {
    app.panel_right.style.display = app.panel_right.style.display == 'block' ? 'none' : 'block';
}
app.show_panel_right = function () {
    app.panel_right.style.display = 'block';
}
app.hide_panel_right = function () {
    app.panel_right.style.display = 'none';
}


window.onload = function () {
    console.log(window.innerWidth);
    console.log(window.innerHeight);
    console.log(window.outerWidth);
    console.log(window.outerHeight);
    console.log(screen.width);
    console.log(screen.height);

    app.init();
}
