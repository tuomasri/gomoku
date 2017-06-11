# Gomoku-harjoitustyö

#### Johdanto

Harjoitustyöaihe oli tällaiseksi kokonaisuudeksi hyvä ja siihen oli saatu sopiva tasapaino työllistävyyden ja keveyden suhteen. Tehtävänantoa lukiessani olin melko varma, että toivottoman raakileeksi jää kun tuntui sen verran työläältä mutta aika nopeaa pelistä sai pelattavan version kasaan. Myönnettäköön, että osa tehtävänannossa ehdotetuista ominaisuuksista jäi tekemättä mutta lauantai-iltana alkoi tuntua siltä, että voisi sitä viikonloppuna muutakin tehdä kuin istua sisällä koodaamassa. 

Itse olen lopputulokseen varsin tyytyväinen. Tiedostan, että pelin ulkoasu jäi melko rumaksi mutta vastapainoksi koodipuolesta tuli varsin selkeää ja luettavaa. PHP tuli valittua kieleksi sen takia, että sitä on tullut kirjoiteltua edelliset viitisen vuotta. Varmaan sama homma olisi mennyt Javallakin mutta ei viitsinyt ottaa riskiä tiukahkon deadlinen kanssa.

#### Käytetyt kielet, kirjastot & riippuvuudet
- PHP7 (Laravel, Doctrine)
- JS (React ja muut pienet apukirjastot)
- Bulma (CSS-framework)
- MySQL

#### Asennus
* .env-tiedostoon tietokannan asetukset (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
* ````composer install -o```` (PHP-kirjastojen asentaminen)
* ````php artisan doctrine:migrations:migrate```` (tietokantamigraatioiden ajaminen)
* JS:t pitäisi olla bundlattuna valmiina

#### Hakemistorakenteet

| Hakemisto | Tarkoitus |
| ------ | ------ |
| /app/Gomoku | Pelin tietomallit ja pelilogiikan koodi |
| /resources/assets/js/components | selainpuolen koodi  |
| /app/Http/Controller | kontrollerit |
| /database/migrations | tietokantamigraatiot  |
| /routes | palvelun routet (api, web) |
| /tests/Feature | muutamia API-testejä |

#### Palvelun routet

| Pyynnön tyyppi | Palvelun route | Tarkoitus |
| ------ | ------ | ------ |
| GET | / | Etusivu
| POST | /api/game | Uuden pelin aloitus
| POST | /api/game/{game}/moves | Siirron lisääminen peliin
| DELETE | /api/game/{game}/moves/{move} | Siirron peruminen

#### Esimerkkipyynnöt & vastaukset

#### 1) Uuden pelin aloitus

Pyyntö: (tyhjä)
Vastaus: 201,
```
{
	id: 1,
	state: 1,
	players: [{
        id: 1,
        color: 1
    }, {
	    id: 2,
        color: 2
    }],
	moves: [],
	winner: null
}
```

#### 2) Siirron lisääminen peliin

Pyyntö:
```
{
	x: 1,
	y: 4,
	player_id: 1
}
```
Vastaus: 201,
```
{
    "id": 1,
	"state": 2,
	"players": [{
	    "id": 1,
		"color": 1
	}, {
	    "id": 2,
		"color": 2
	}],
	"moves": [{
	    "id": 1,
		"x": 1,
		"y": 4,
		"isWinningMove": false,
		"dateCreated": "2017-06-1014:38:26",
	    "playerId": 1
	}],
    "winner": null
} 
```

#### 3) Siirron peruminen (onnistuu vain viimeisimmälle siirrolle 5 sek. sisään siirron tekoajasta)
Pyyntö: (tyhjä)
Vastaus: sama kuin kohdassa 1 tai 2 riippuen oliko kyseessä pelin ensimmäinen siirto vai ei

#### Muutoshistoria / työn eteneminen

##### 06.06.2017
* Laravelin + Doctrinen asennus (ei vielä paljon mitään konfigurointia)
* testaus sen verran, että projekti toimii (eli Apache näyttää defaulttietusivun)
* domainin & tietomallien karkean tason hahmottelua
* APIn hahmottelua

#### 07.06.2017
* aamulla voittoresolvoinnin tuumintaa (ts. miten päätellään, että peli on voitettu)
    * alustava luonnos aiheesta kasaan, ei testattu
* tietomallin toteutusta
*  Laravelin & Doctrinen konfigurointia & yksinkertainen serialisointi toimimaan
*  APIn testailua

#### 08.06.2017
* voittoresolvoinnin testailua REPLin kautta; huomattu, että toteutus ei toimi kuin tietyissä tapauksissa
    * koodipuolta näiltä osin uusiksi
* frontin suunnittelua ja toteutusta
    * yöhön mennessä karuhko versio pelistä valmis joka toimii jo suurinpiirtein

#### 09.06.2017
* viilailua sekä palvelin- että selainpuolelle 
* lisätty voittavien siirtojen tallennus (jolloin voidaan näyttää frontilla 5:n voittavan siirron ketju)
* tehtävä pikkuhiljaa paketissa, toteuttamatta uuden pelin aloitus ja tasapelin testaus	
	
#### 10.06.2017
* aamupäivän aikana tehty vielä siirron peruminen (viimeisimmän siirron voi perua 5 sekunnin sisällä sen tekemisestä)
* pientä hiomista fronttiin
* tasapelin resolvoinnin testaus, toimii
* tämän dokumentin viimeistelyä
* muutamia API-testejä kirjoitettu harjoituksen vuoksi

#### 11.06.2017
* lisää API-testejä 
* pientä siistimistä / parantelua domain-entityihin