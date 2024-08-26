#!/bin/bash

# Obtener la fecha actual
current_date=$(date +'%Y-%m-%d')

# Continuamos con el curl original para obtener el reporte del día actual
# curl -k --location --request GET 'https://reportservices.hmil.com.ni/api/reporte-eventos?tipo_busquedad=1' \
# --header 'Content-Type: application/json' \
# --data-raw '{
#     "fecha": "",
#     "destinatary": [            
#             { 
#                 "name": "Cesar Cuadra",
#                 "email": "cesar.cuadra@hospitalmilitar.com.ni"
#             }
#         ],
#     "subject": "Reporte Diario de Personas Inscritas XXI Congreso y Precongreso Cientifíco Médico",
#     "body": "Se detalla La cantidad de personas inscritas para el evento XXI Congreso y Precongreso Cientifíco Médico, para llevar un control del conteo diario con corte a las 15hrs. Muchas Gracias"
# }'


#!/bin/bash

# Obtener la fecha actual
current_date=$(date +'%Y-%m-%d')

# Continuamos con el curl original para obtener el reporte del d  a actual

curl -k --location --request GET 'https://reportservices.hmil.com.ni/api/reporte-eventos?tipo_busquedad=1' \
--header 'Content-Type: application/json' \
--data-raw '{
    "fecha": "",
    "destinatary": [            
            { 
                "name": "Cesar Cuadra",
                "email": "cesar.cuadra@hospitalmilitar.com.ni"
            },
            { 
                "name": "Administradora de Gerencia Eventos Internacionales",
                "email": "cristhell.roiz@hospitalmilitar.com.ni"
            },
        ],
    "subject": "Reporte Diario de Personas Inscritas XXI Congreso y Precongreso Cientifico Medico",
    "body": "Se detalla La cantidad de personas inscritas para el evento XXI Congreso y Precongreso Cientifico Medico, para llevar un control del conteo diario con corte a las 15hrs. Muchas Gracias"
}'
