/*
 * KR Verhuur – assortiment & instellingen
 * ---------------------------------------
 * Dit is het enige bestand dat je hoeft aan te passen om huurgroepen,
 * producten, prijzen, extra opties en bezette dagen te beheren.
 *
 * Prijzen zijn in euro's, inclusief btw. Alle prijzen hieronder zijn
 * VOORBEELDPRIJZEN – vervang ze door de echte tarieven.
 */
window.KR = window.KR || {};

KR.settings = {
  companyName: "KR Verhuur",
  email: "info@kr-verhuur.nl",
  phone: "06 00 00 00 00",
  phoneHref: "+31600000000",
  region: "Regio – vul plaats in",

  // Link naar de camper(s) op Goboony.
  goboonyUrl: "https://www.goboony.nl/",

  // Waar een boekingsaanvraag heen gaat.
  //  - leeg laten ("")  → de aanvraag wordt als e-mail geopend (mailto)
  //  - een URL invullen → de aanvraag wordt als JSON gePOST (bijv. Formspree,
  //    een eigen API of later de winkelwagen/checkout)
  bookingEndpoint: "",

  // Hoeveel dagen vooruit er geboekt kan worden
  maxDaysAhead: 365,
  // Minimaal aantal dagen tussen vandaag en de eerste huurdag
  minLeadDays: 1,
};

/*
 * Extra opties die per product aangezet kunnen worden.
 *  type "fixed"   → vast bedrag per boeking
 *  type "perDay"  → bedrag per huurdag
 */
KR.extras = {
  delivery:   { label: "Halen en brengen",        description: "Wij bezorgen, plaatsen en halen weer op (binnen 25 km).", price: 45, type: "fixed" },
  setup:      { label: "Opbouwen en afbreken",    description: "Wij bouwen alles voor je op en weer af.",                 price: 35, type: "fixed" },
  cleaning:   { label: "Schoonmaakkosten",        description: "Geen gedoe achteraf: wij maken alles schoon.",            price: 30, type: "fixed" },
  toiletCleaning: { label: "Schoonmaak & afvoer",  description: "Legen, reinigen en afvoeren van afvalwater.",           price: 75, type: "fixed" },
  supplies:   { label: "Toiletpakket",            description: "Toiletpapier, handzeep en papieren handdoekjes.",        price: 15, type: "fixed" },
  printPack:  { label: "Extra printpakket",       description: "400 extra prints voor de photobooth.",                    price: 40, type: "fixed" },
  props:      { label: "Props & accessoires",     description: "Brillen, hoeden, borden en meer.",                       price: 15, type: "fixed" },
  attendant:  { label: "Begeleiding",             description: "Een medewerker aanwezig tijdens je feest (per dag).",   price: 95, type: "perDay" },
  fuel:       { label: "Brandstof / gasfles",     description: "Volle gasfles of tank brandstof inbegrepen.",            price: 25, type: "perDay" },
  tablecloths:{ label: "Tafelkleden",             description: "Witte tafelkleden, gewassen en gestreken.",              price: 20, type: "fixed" },
  insurance:  { label: "Breukverzekering",        description: "Schade door breuk verzekerd (eigen risico € 50).",     price: 10, type: "perDay" },
};

/*
 * Huurgroepen.
 *  externalUrl → de groep linkt direct naar een externe site (bijv. Goboony)
 */
KR.groups = [
  { id: "camper",      name: "Camper",       icon: "camper",   tagline: "Op avontuur met onze camper – boeken via Goboony.", externalUrl: "goboony" },
  { id: "photobooth",  name: "Photobooth",   icon: "camera",   tagline: "Onvergetelijke foto's op elk feest." },
  { id: "toiletwagen", name: "Toiletwagen",  icon: "toilet",   tagline: "Nette, schone sanitaire voorzieningen." },
  { id: "springkussen",name: "Springkussen", icon: "castle",   tagline: "Uren springplezier voor jong en oud." },
  { id: "bumperbaan",  name: "Bumperbaan",   icon: "bumper",   tagline: "Botsen, lachen en racen in de bumperbaan." },
  { id: "meubilair",   name: "Meubilair",    icon: "chair",    tagline: "Statafels, bierbanken, stoelen en tafels." },
  { id: "verwarming",  name: "Verwarming",   icon: "flame",    tagline: "Terrasheaters en heaters voor elk seizoen." },
  { id: "gereedschap", name: "Gereedschap",  icon: "tool",     tagline: "Professioneel gereedschap voor elke klus." },
];

/*
 * Producten.
 *  pricePerDay       → prijs eerste dag
 *  pricePerExtraDay  → prijs voor elke volgende dag (optioneel, anders = pricePerDay)
 *  deposit           → borg (alleen ter informatie)
 *  maxDays           → maximaal aantal huurdagen in één boeking
 *  extras            → keys uit KR.extras die bij dit product gekozen kunnen worden
 *  bookedDates       → al verhuurde dagen (YYYY-MM-DD) – worden in de kalender geblokkeerd
 */
KR.products = [
  // Photobooth
  { id: "photobooth-classic", group: "photobooth", name: "Photobooth Classic",
    description: "Complete photobooth met touchscreen, studiolamp en directe fotoprints. Inclusief 400 prints en digitale galerij.",
    features: ["Directe prints (10x15)", "Digitale galerij", "Eigen tekst op de foto"],
    pricePerDay: 249, pricePerExtraDay: 125, deposit: 150, maxDays: 3,
    extras: ["delivery", "setup", "printPack", "props", "attendant"], bookedDates: [] },
  { id: "photobooth-mirror", group: "photobooth", name: "Magic Mirror",
    description: "Een spiegel op ware grootte die foto's maakt. Met animaties en een eigen rand op de foto.",
    features: ["Full-length spiegel", "Animaties & touch", "Onbeperkt prints"],
    pricePerDay: 349, pricePerExtraDay: 175, deposit: 250, maxDays: 3,
    extras: ["delivery", "setup", "printPack", "props", "attendant"], bookedDates: [] },

  // Toiletwagen
  { id: "toiletwagen-luxe", group: "toiletwagen", name: "Luxe toiletwagen",
    description: "Luxe toiletwagen met aparte dames- en herenruimte, verlichting, verwarming en stromend water.",
    features: ["2 toiletten + 2 urinoirs", "Wastafels met stromend water", "Verlichting & verwarming"],
    pricePerDay: 295, pricePerExtraDay: 95, deposit: 250, maxDays: 14,
    extras: ["delivery", "toiletCleaning", "supplies"], bookedDates: [] },

  // Springkussen
  { id: "springkussen-kasteel", group: "springkussen", name: "Springkussen Kasteel",
    description: "Kleurrijk springkasteel voor kinderen tot 12 jaar. Afmeting 4 x 4 meter.",
    features: ["4 x 4 m", "Tot 8 kinderen", "Inclusief blower"],
    pricePerDay: 85, pricePerExtraDay: 45, deposit: 100, maxDays: 7,
    extras: ["delivery", "setup", "cleaning"], bookedDates: [] },
  { id: "springkussen-stormbaan", group: "springkussen", name: "Stormbaan",
    description: "Uitdagende stormbaan met hindernissen en glijbaan. Afmeting 10 x 3 meter.",
    features: ["10 x 3 m", "Voor jong en oud", "Inclusief blowers"],
    pricePerDay: 175, pricePerExtraDay: 90, deposit: 150, maxDays: 7,
    extras: ["delivery", "setup", "cleaning", "attendant"], bookedDates: [] },

  // Bumperbaan
  { id: "bumperbaan", group: "bumperbaan", name: "Bumperbaan compleet",
    description: "Opblaasbare baan met elektrische bumperauto's. Een topper op elk evenement.",
    features: ["Baan 12 x 8 m", "6 bumperauto's", "Laders inbegrepen"],
    pricePerDay: 495, pricePerExtraDay: 250, deposit: 300, maxDays: 5,
    extras: ["delivery", "setup", "attendant", "cleaning"], bookedDates: [] },

  // Meubilair
  { id: "statafel", group: "meubilair", name: "Statafel (per stuk)",
    description: "Stevige statafel met een diameter van 80 cm. Mooi met een rok of kleed.",
    features: ["Ø 80 cm", "Hoogte 110 cm", "Inklapbaar"],
    pricePerDay: 7.5, pricePerExtraDay: 3, deposit: 0, maxDays: 14, quantity: true,
    extras: ["delivery", "tablecloths", "cleaning"], bookedDates: [] },
  { id: "biertafelset", group: "meubilair", name: "Biertafelset",
    description: "Tafel met twee banken, geschikt voor 8 personen. Binnen en buiten te gebruiken.",
    features: ["8 personen", "220 x 50 cm", "Makkelijk te vervoeren"],
    pricePerDay: 12.5, pricePerExtraDay: 5, deposit: 0, maxDays: 14, quantity: true,
    extras: ["delivery", "cleaning"], bookedDates: [] },

  // Verwarming
  { id: "terrasheater", group: "verwarming", name: "Terrasheater op gas",
    description: "Staande terrasheater van 13 kW. Voor een warm terras of feest buiten.",
    features: ["13 kW", "Hoogte 2,2 m", "Werkt op propaan"],
    pricePerDay: 35, pricePerExtraDay: 15, deposit: 50, maxDays: 14, quantity: true,
    extras: ["delivery", "fuel", "cleaning"], bookedDates: [] },
  { id: "heater-diesel", group: "verwarming", name: "Heteluchtkanon",
    description: "Krachtig heteluchtkanon voor tenten, loodsen en bouwplaatsen.",
    features: ["30 kW", "Met thermostaat", "Voor tent of hal"],
    pricePerDay: 65, pricePerExtraDay: 30, deposit: 100, maxDays: 30,
    extras: ["delivery", "fuel"], bookedDates: [] },

  // Gereedschap
  { id: "trilplaat", group: "gereedschap", name: "Trilplaat",
    description: "Trilplaat voor het verdichten van zand en grind. Ideaal voor bestrating.",
    features: ["90 kg", "Benzinemotor", "Werkbreedte 50 cm"],
    pricePerDay: 55, pricePerExtraDay: 35, deposit: 100, maxDays: 14,
    extras: ["delivery", "fuel", "cleaning", "insurance"], bookedDates: [] },
  { id: "hogedrukreiniger", group: "gereedschap", name: "Hogedrukreiniger",
    description: "Professionele hogedrukreiniger voor terrassen, opritten en gevels.",
    features: ["200 bar", "Inclusief vuilfrees", "Snoerlengte 10 m"],
    pricePerDay: 45, pricePerExtraDay: 25, deposit: 50, maxDays: 14,
    extras: ["delivery", "cleaning", "insurance"], bookedDates: [] },
  { id: "breekhamer", group: "gereedschap", name: "Breekhamer",
    description: "Zware breekhamer voor sloopwerk aan beton en tegels.",
    features: ["1500 W", "Inclusief beitels", "In koffer"],
    pricePerDay: 39, pricePerExtraDay: 25, deposit: 75, maxDays: 14,
    extras: ["delivery", "cleaning", "insurance"], bookedDates: [] },
];
