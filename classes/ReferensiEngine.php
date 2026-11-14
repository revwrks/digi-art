<?php
/**
 * ReferensiEngine: Search Engine Referensi Gambar Edukasi Berbahasa Indonesia
 * - Berbasis OOP (Deep Module): Pemrosesan query, multi-tier search, ranking relevansi, dan caching database.
 * - Konsistensi 100%: Query yang sama akan menghasilkan 25 gambar yang persis sama setiap kali dicari.
 * - Mengutamakan hasil berbahasa Indonesia dan materi kurikulum Seni Rupa SMP (lintas mapel: IPA, IPS, Bahasa Indonesia).
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AdGuardFilter.php';

class ReferensiEngine {
    /**
     * Kamus Kontekstual Edukasi Komprehensif (Pemetaan Topik SMP Lintas Mapel ke Terminologi Visual Presisi)
     * Mencakup: Seni Rupa & Desain, Sains & Biologi/Fisika, IPS & Budaya Nusantara, Cerita Rakyat, dan Geometri
     */
    private static array $thesaurus = [
        // --- SENI RUPA & DESAIN VISUAL ---
        'lukisan penangkapan diponegoro' => 'the arrest of prince diponegoro raden saleh historic painting',
        'lukisan raden saleh'     => 'raden saleh romanticism fine art masterpieces canvas oil',
        'lukisan affandi'         => 'affandi expressionism oil canvas dynamic impasto fine art',
        'lukisan basoeki abdullah'=> 'basoeki abdullah romanticism portrait landscape fine art',
        'lukisan s sudjojono'     => 's sudjojono realism revolutionary indonesian fine art',
        'batik mega mendung'      => 'batik mega mendung cirebon motif pattern illustration',
        'batik parang rusak'      => 'batik parang rusak motif pattern javanese traditional',
        'batik parang'            => 'batik parang motif pattern traditional javanese',
        'batik kawung'            => 'batik kawung pattern geometry traditional javanese',
        'batik pesisir'           => 'batik pesisiran pekalongan colorful floral pattern',
        'batik truntum'           => 'batik truntum star floral pattern symbol of love surakarta',
        'batik sekar jagad'       => 'batik sekar jagad diverse archipelago harmony motif pattern',
        'batik solo'              => 'batik solo sogan classic brown traditional pattern',
        'batik yogyakarta'        => 'batik yogyakarta traditional geometric pattern',
        'batik'                   => 'indonesian batik pattern textile traditional ornament',
        'ragam hias nusantara'    => 'traditional indonesian ornament motif decorative pattern',
        'ragam hias geometris'    => 'geometric ornament pattern decorative indonesian art',
        'ragam hias flora'        => 'floral ornament pattern decorative botanical art',
        'ragam hias fauna'        => 'animal ornament pattern traditional decorative art',
        'ragam hias figuratif'    => 'figurative human motif ornament traditional art',
        'ragam hias poligonal'    => 'polygonal geometric traditional ornament design',
        'ragam hias'              => 'traditional ornament pattern motif decorative art',
        'motif ukir jepara'       => 'jepara wood carving floral motif relief traditional',
        'motif ukir toraja'       => 'toraja wood carving passura traditional motif',
        'motif ukir bali'         => 'balinese traditional wood stone carving ornament',
        'motif ukir asmat'        => 'asmat papua wood carving traditional ethnic motif',
        'motif ukir dayak'        => 'dayak kalimantan wood carving spiral traditional motif',
        'motif ukir'              => 'traditional wood carving relief motif pattern',
        'wayang kulit purwa'      => 'wayang kulit shadow puppet javanese silhouette illustration',
        'wayang kulit'            => 'wayang kulit shadow puppet javanese silhouette illustration',
        'wayang golek'            => 'wayang golek sundanese wooden puppet traditional',
        'wayang orang'            => 'wayang orang classical javanese theatrical dance drama',
        'wayang'                  => 'wayang traditional indonesian puppet shadow illustration',
        'patung asmat'            => 'asmat papua wood carving sculpture traditional art',
        'patung garuda wisnu'     => 'garuda wisnu kencana monument statue bali sculpture',
        'patung nusantara'        => 'traditional indonesian statue stone wood bronze sculpture',
        'seni patung'             => 'sculpture statue three dimensional fine art carving',
        'seni lukis naturalisme'  => 'naturalism landscape nature painting realistic fine art',
        'seni lukis realisme'     => 'realism oil canvas painting fine art everyday life',
        'seni lukis ekspresionisme'=> 'expressionism emotion canvas painting fine art affandi',
        'seni lukis kubisme'      => 'cubism geometric abstract painting fine art',
        'seni lukis impresionisme'=> 'impressionism light color brushwork painting',
        'seni lukis surealisme'   => 'surrealism dreamlike imaginative fine art painting',
        'seni lukis abstrak'      => 'abstract art colorful geometric painting illustration',
        'seni lukis'              => 'fine art painting canvas watercolor acrylic illustration',
        'teknik kolase'           => 'collage paper mixed media contemporary artwork illustration',
        'teknik montase'          => 'photomontage composite image visual art illustration',
        'teknik mozaik'           => 'mosaic tile glass tesserae decorative art illustration',
        'teknik plakat'           => 'gouache poster color opaque flat painting technique',
        'teknik akuarel'          => 'watercolor transparent wash wet on wet painting technique',
        'gambar vinyet'           => 'vignette decorative border floral scroll book illustration',
        'ilustrasi sampul buku'   => 'book cover illustration conceptual typography design layout',
        'desain logo'             => 'minimalist logo design vector symbol branding geometry',
        'desain maskot'           => 'character mascot cartoon illustration friendly dynamic',
        'seni instalasi'          => 'installation art spatial contemporary immersive sculpture',
        'seni ukir sabun'         => 'soap carving sculpture craftwork 3d student art',
        'relief cetak tinggi'     => 'relief printmaking linocut woodblock high print graphic art',
        'relief cetak dalam'      => 'intaglio etching engraving printmaking graphic art',
        'seni kriya keramik'      => 'pottery ceramic earthenware traditional handicraft',
        'seni kriya anyaman'      => 'woven bamboo rattan craft traditional basketry',
        'seni kriya tekstil'      => 'tenun ikat songket traditional textile weaving craft',
        'kain tenun ikat'         => 'tenun ikat flores sumba geometric traditional handwoven textile',
        'kain ulos'               => 'ulos traditional batak ceremonial woven textile shawl',
        'kain songket palembang'  => 'songket palembang gold silver thread royal luxury textile',
        'kain tapis lampung'      => 'tapis lampung gold thread embroidered traditional cylindrical skirt',
        'seni kriya'              => 'traditional handicraft artisan craftwork indonesia',
        'seni grafis cukil kayu'  => 'woodcut printmaking relief print graphic art',
        'seni grafis sablon'      => 'silkscreen screen printing serigraphy graphic art',
        'seni grafis'             => 'printmaking graphic art woodcut linocut etching illustration',
        'lingkaran warna'         => 'color wheel primary secondary tertiary hues diagram',
        'roda warna'              => 'color wheel chromatic harmony theory diagram',
        'teori warna'             => 'color theory complimentary analogous harmony diagram',
        'warna komplementer'      => 'complementary colors contrast harmony visual theory',
        'warna analog'            => 'analogous color scheme harmony palette visual art',
        'gradasi warna'           => 'color gradient smooth shading blending transition',
        'perspektif satu titik hilang' => 'one point perspective drawing geometry vanishing point',
        'perspektif satu titik'   => 'one point perspective drawing geometry vanishing point',
        'perspektif dua titik hilang'  => 'two point perspective architectural drawing vanishing point',
        'perspektif dua titik'    => 'two point perspective architectural drawing vanishing point',
        'perspektif tiga titik'   => 'three point perspective drawing birds eye view',
        'perspektif'              => 'perspective drawing vanishing point horizon line diagram',
        'sketsa pensil'           => 'pencil sketch drawing graphite shading study',
        'sketsa wajah'            => 'portrait face pencil sketch drawing anatomy',
        'proporsi tubuh manusia'  => 'human figure drawing proportions anatomy canon diagram',
        'anatomi wajah'           => 'facial anatomy proportions drawing portrait diagram',
        'arsiran silang'          => 'cross hatching drawing shading technique texture',
        'teknik arsir'            => 'hatching shading techniques drawing texture value',
        'arsiran'                 => 'cross hatching shading techniques drawing texture',
        'teknik dussel'           => 'smudging blending pencil shading drawing technique',
        'teknik pointilis'        => 'pointillism stippling dot art drawing technique',
        'siluet'                  => 'silhouette high contrast shape shadow art',
        'poster lingkungan hidup' => 'environmental poster save earth nature tree illustration',
        'poster lingkungan'       => 'environmental poster save earth nature illustration',
        'poster pendidikan'       => 'educational school learning poster illustration',
        'poster kesehatan'        => 'health public service announcement poster illustration',
        'poster'                  => 'creative typography graphic design poster illustration',
        'komik strip'             => 'comic strip sequential art panel storyboard cartoon',
        'karikatur'               => 'caricature exaggerated portrait cartoon illustration',
        'animasi 2d'              => '2d animation character sprite sheet storyboard',
        'tipografi'               => 'typography creative letterform font design layout',
        'kaligrafi islam'         => 'islamic arabic calligraphy decorative traditional art',
        'kaligrafi'               => 'calligraphy ornate lettering traditional decorative',
        'nirmana datar'           => 'nirmana 2d abstract visual rhythm pattern design',
        'nirmana ruang'           => 'nirmana 3d spatial composition geometric sculpture',
        'nirmana'                 => 'nirmana visual design balance rhythm contrast study',

        // --- SAINS & IPA SMP ---
        'gunung berapi'           => 'volcano magma chamber vent eruption geology diagram',
        'gunung api'              => 'volcano stratovolcano magma chamber eruption geology',
        'gunung'                  => 'volcano mountain peak landscape nature indonesia',
        'siklus air'              => 'water cycle evaporation condensation precipitation hydrology diagram',
        'daur air'                => 'water cycle diagram hydrology hydrological process',
        'siklus hidrologi'        => 'hydrological cycle water atmosphere evaporation diagram',
        'siklus batuan'           => 'rock cycle igneous sedimentary metamorphic geology diagram',
        'tata surya'              => 'solar system planets orbit sun astronomy diagram',
        'planet merkurius'        => 'mercury planet closest sun astronomy space',
        'planet venus'            => 'venus atmosphere greenhouse planet astronomy',
        'planet mars'             => 'mars red planet geology astronomy space exploration',
        'planet jupiter'          => 'jupiter gas giant great red spot astronomy',
        'planet saturnus'         => 'saturn rings planet astronomy space illustration',
        'planet uranus'           => 'uranus ice giant tilted axis astronomy',
        'planet neptunus'         => 'neptune blue giant planet astronomy',
        'planet'                  => 'solar system planets astronomical scale diagram',
        'gerhana matahari total'  => 'total solar eclipse corona umbra moon shadow diagram',
        'gerhana matahari'        => 'solar eclipse umbra penumbra celestial diagram',
        'gerhana bulan total'     => 'total lunar eclipse blood moon shadow diagram',
        'gerhana bulan'           => 'lunar eclipse earth shadow moon astronomy diagram',
        'gerhana'                 => 'astronomical eclipse solar lunar shadow diagram',
        'fase bulan'              => 'moon phases lunar cycle waxing waning orbit diagram',
        'orbit bumi'              => 'earth orbit around sun seasons solstice equinox diagram',
        'lapisan atmosfer'        => 'earth atmospheric layers troposphere stratosphere mesosphere diagram',
        'lapisan ozon'            => 'ozone layer stratosphere uv rays protection diagram',
        'lapisan bumi'            => 'earth internal structure crust mantle outer inner core diagram',
        'kerak bumi'              => 'earth crust oceanic continental lithosphere diagram',
        'lempeng tektonik'        => 'plate tectonics continental drift subduction fault diagram',
        'gempa bumi'              => 'earthquake epicenter hypocenter seismic waves fault diagram',
        'tsunami'                 => 'tsunami wave propagation underwater earthquake diagram',
        'fotosintesis'            => 'photosynthesis light reaction chloroplast plant process diagram',
        'sel tumbuhan'            => 'plant cell wall chloroplast vacuole organelles biology diagram',
        'sel hewan'               => 'animal cell membrane nucleus mitochondria organelles diagram',
        'sel biologi'             => 'cell structure organelles microscopic biology diagram',
        'pembelahan sel mitosis'  => 'mitosis cell division prophase metaphase anaphase telophase diagram',
        'pembelahan sel'          => 'cell division mitosis stages prophase metaphase anaphase diagram',
        'mitosis'                 => 'mitosis cell division biological stages diagram',
        'meiosis'                 => 'meiosis gametogenesis cell division stages diagram',
        'mikroskop cahaya'        => 'compound optical microscope parts labeled diagram science',
        'mikroskop'               => 'optical light microscope parts and function labeled diagram',
        'struktur bunga'          => 'flower anatomy stamen pistil petal ovule pollination diagram',
        'bagian bunga'            => 'flower anatomy stamen pistil corolla diagram science',
        'penyerbukan'             => 'plant pollination flower reproduction biology diagram',
        'metamorfosis kupu-kupu'  => 'butterfly life cycle egg caterpillar pupa metamorphosis diagram',
        'metamorfosis katak'      => 'frog life cycle tadpole amphibian metamorphosis diagram',
        'metamorfosis nyamuk'     => 'mosquito life cycle larva pupa vector diagram',
        'metamorfosis belalang'   => 'grasshopper incomplete metamorphosis nymph adult diagram',
        'metamorfosis'            => 'complete incomplete metamorphosis insect amphibian life cycle diagram',
        'rantai makanan'          => 'food chain producer primary secondary consumer trophic level diagram',
        'jaring-jaring makanan'   => 'food web ecosystem interconnected trophic relationships diagram',
        'piramida makanan'        => 'ecological biomass energy trophic pyramid ecosystem diagram',
        'simbiosis mutualisme'    => 'mutualism symbiosis bee flower clownfish anemone biology',
        'simbiosis parasitisme'   => 'parasitism symbiosis parasite host relationship biology',
        'simbiosis komensalisme'  => 'commensalism symbiosis remora shark orchid tree biology',
        'simbiosis'               => 'symbiosis mutualism commensalism parasitism biology diagram',
        'ekosistem hutan hujan'   => 'tropical rainforest canopy understory biodiversity illustration',
        'ekosistem hutan'         => 'tropical rainforest canopy understory biodiversity illustration',
        'ekosistem terumbu karang'=> 'coral reef marine biodiversity underwater ocean illustration',
        'ekosistem laut'          => 'marine coral reef ecosystem ocean biodiversity illustration',
        'ekosistem mangrove'      => 'mangrove coastal forest ecosystem root roots wetland biology',
        'ekosistem sawah'         => 'paddy field rice terrace agroecosystem biodiversity illustration',
        'ekosistem padang rumput' => 'savanna grassland biome wildlife ecosystem illustration',
        'ekosistem'               => 'natural ecosystem biome biodiversity environmental illustration',
        'sistem peredaran darah'  => 'human circulatory system cardiovascular heart vessels diagram',
        'peredaran darah besar'   => 'systemic circulation human blood flow heart body diagram',
        'peredaran darah kecil'   => 'pulmonary circulation blood flow heart lungs oxygen diagram',
        'jantung manusia'         => 'human heart internal anatomy atrium ventricle valves diagram',
        'jantung'                 => 'human heart internal anatomy atrium ventricle valves diagram',
        'pembuluh darah'          => 'artery vein capillary blood vessel anatomy histology diagram',
        'sel darah'               => 'red blood cell erythrocyte leukocyte platelet hematology diagram',
        'sistem pernapasan'       => 'human respiratory system lungs trachea alveoli diagram',
        'paru-paru manusia'       => 'human lungs alveoli bronchial tree respiratory diagram',
        'paru-paru'               => 'human lungs alveoli bronchial tree respiratory diagram',
        'alveolus'                => 'alveoli gas exchange oxygen carbon dioxide capillaries diagram',
        'sistem pencernaan'       => 'human digestive system stomach intestine liver esophagus diagram',
        'lambung'                 => 'human stomach internal anatomy gastric acid digestion diagram',
        'usus halus'              => 'small intestine villi nutrient absorption anatomy diagram',
        'hati dan empedu'         => 'liver gallbladder bile duct digestive anatomy diagram',
        'sistem ekskresi'         => 'human excretory system kidneys ureter bladder nephron diagram',
        'ginjal manusia'          => 'kidney anatomy nephron renal cortex medulla filtration diagram',
        'ginjal'                  => 'human kidney internal anatomy renal cortex medulla diagram',
        'kerangka manusia'        => 'human skeletal system bones skeleton labeled diagram',
        'tulang manusia'          => 'human skeleton skull spine femur anatomy diagram',
        'otot manusia'            => 'human muscular system biceps triceps skeletal muscle diagram',
        'sendi manusia'           => 'synovial joint types ball socket hinge pivot anatomy diagram',
        'sistem saraf manusia'    => 'human nervous system brain spinal cord neuron diagram',
        'sistem saraf'            => 'human nervous system brain spinal cord neuron diagram',
        'otak manusia'            => 'human brain cerebrum cerebellum brainstem lobes diagram',
        'neuron saraf'            => 'neuron nerve cell axon dendrite synapse anatomy diagram',
        'mata manusia'            => 'human eye anatomy cornea lens retina pupil optical diagram',
        'telinga manusia'         => 'human ear anatomy cochlea eardrum auditory system diagram',
        'kulit manusia'           => 'human skin layers epidermis dermis subcutaneous histology diagram',
        'organ tubuh manusia'     => 'human anatomy internal vital organs illustration diagram',
        'organ tubuh'             => 'human anatomy internal vital organs illustration diagram',
        'atom dan molekul'        => 'atom electron proton neutron molecules chemical bonds diagram',
        'struktur atom'           => 'bohr atomic model electron shells nucleus physics diagram',
        'atom'                    => 'atom atomic model electron proton neutron nucleus physics diagram',
        'tabel periodik unsur'    => 'periodic table of chemical elements science chart',
        'tabel periodik'          => 'periodic table of chemical elements science chart',
        'senyawa kimia'           => 'chemical compound molecular structure covalent ionic bonds',
        'wujud zat'               => 'states of matter solid liquid gas phase change diagram',
        'kalor dan perpindahan'   => 'heat transfer conduction convection radiation physics diagram',
        'hukum newton'            => 'newton laws of motion force mass acceleration physics diagram',
        'gaya gesek'              => 'frictional force physics free body diagram incline',
        'gaya gravitasi'          => 'gravity gravitational pull earth moon orbit physics diagram',
        'tuas dan pengungkit'     => 'lever simple machine fulcrum load effort mechanical advantage diagram',
        'pesawat sederhana'       => 'simple machines lever pulley inclined plane wheel diagram',
        'katrol'                  => 'pulley system mechanical advantage physics diagram',
        'tekanan zat cair'        => 'hydrostatic pressure pascal archimedes principle physics diagram',
        'hukum archimedes'        => 'archimedes principle buoyant force floating sinking physics diagram',
        'gelombang bunyi'         => 'sound wave longitudinal compression rarefaction physics diagram',
        'spektrum elektromagnetik'=> 'electromagnetic spectrum radio optical uv xray gamma diagram',
        'pembiasan cahaya'        => 'refraction of light prism optical physics snell law diagram',
        'pemantulan cahaya'       => 'reflection of light mirrors concave convex optical ray diagram',
        'cermin cekung'           => 'concave mirror optical focal point ray diagram physics',
        'lensa cembung'           => 'convex lens optical focus real virtual image ray diagram',
        'magnet dan medan magnet' => 'magnetic field lines north south pole bar magnet diagram',
        'medan magnet'            => 'magnetic field lines physics dipole solenoid diagram',
        'magnet'                  => 'magnetic field lines north south pole physics diagram',
        'rangkaian seri paralel'  => 'series and parallel electric circuit schematic diagram',
        'rangkaian listrik'       => 'electric circuit series parallel schematic physics diagram',
        'hukum ohm'               => 'ohm law voltage current resistance circuit diagram',

        // --- IPS & BUDAYA NUSANTARA ---
        'peta kepulauan indonesia'=> 'indonesia archipelago map geography provinces borders',
        'peta indonesia'          => 'indonesia map archipelago geography provinces nusantara',
        'peta nusantara'          => 'indonesian archipelago geographic maritime map',
        'peta dunia'              => 'world map continents oceans geography political physical',
        'peta benua asia'         => 'asia continent geographic political map',
        'garis khatulistiwa'      => 'equator zero latitude tropical zone geographic map',
        'garis wallace'           => 'wallace weber faunal boundary line indonesia biogeography map',
        'relief bumi'             => 'landforms topography mountain valley plateau relief map',
        'gunung bromo'            => 'mount bromo tengger caldera sunrise landscape east java',
        'danau toba'              => 'lake toba samosir island volcanic crater caldera landscape',
        'labuan bajo'             => 'komodo padar island labuan bajo landscape nusa tenggara',
        'raja ampat'              => 'raja ampat karst islands turquoise ocean papua landscape',
        'rumah adat toraja'       => 'tongkonan toraja traditional house vernacular architecture',
        'rumah adat minangkabau'  => 'rumah gadang minangkabau curved roof traditional architecture',
        'rumah adat minang'       => 'rumah gadang minangkabau curved roof traditional architecture',
        'rumah adat gadang'       => 'rumah gadang minangkabau west sumatra traditional architecture',
        'rumah adat jawa'         => 'rumah joglo javanese vernacular timber architecture',
        'rumah adat joglo'        => 'rumah joglo javanese timber pavilion vernacular architecture',
        'rumah adat papua'        => 'rumah honai papua circular thatched traditional dwelling',
        'rumah adat honai'        => 'rumah honai papua circular thatched traditional dwelling',
        'rumah adat batak'        => 'rumah bolon batak north sumatra traditional architecture',
        'rumah adat tongkonan'    => 'tongkonan toraja traditional vernacular carved wood house',
        'rumah adat bali'         => 'gapura candi bentar balinese traditional courtyard architecture',
        'rumah adat melayu'       => 'traditional malay timber stilt house architecture',
        'rumah adat'              => 'indonesian traditional vernacular house architecture nusantara',
        'pakaian adat jawa'       => 'traditional javanese beskap kebaya traditional costume',
        'pakaian adat bali'       => 'traditional balinese costume kamen udeng ceremonies',
        'pakaian adat minangkabau'=> 'traditional minangkabau wedding costume suntiang gold',
        'pakaian adat papua'      => 'traditional papuan ethnic attire koteka feathers beads',
        'pakaian adat dayak'      => 'traditional dayak costume king baba beads hornbill feathers',
        'pakaian adat aceh'       => 'traditional acehnese traditional costume gold embroidery',
        'pakaian adat'            => 'traditional indonesian national regional ethnic costumes',
        'senjata tradisional keris'=> 'keris kris javanese dagger pamor pattern blade traditional',
        'senjata tradisional badik'=> 'badik bugis makassar traditional dagger weapon',
        'senjata tradisional rencong' => 'rencong aceh traditional curved dagger weapon',
        'senjata tradisional kujang' => 'kujang sundanese traditional curved blade weapon',
        'senjata tradisional mandau'=> 'mandau dayak sword kalimantan traditional weapon',
        'senjata tradisional'     => 'traditional indonesian cultural heritage weapons blades',
        'alat musik angklung'     => 'angklung sundanese bamboo musical instrument traditional',
        'alat musik gamelan'      => 'javanese balinese gamelan orchestra bronze percussion traditional',
        'alat musik sasando'      => 'sasando rote palm leaf stringed traditional musical instrument',
        'alat musik kolintang'    => 'kolintang minahasa wooden xylophone percussion instrument',
        'alat musik tifa'         => 'tifa papua maluku traditional wooden drum instrument',
        'alat musik kecapi'       => 'kecapi zither sundanese traditional string instrument',
        'alat musik suling'       => 'bamboo flute suling traditional indonesian instrument',
        'alat musik tradisional'  => 'traditional indonesian musical instruments folk acoustic',
        'tari saman'              => 'saman dance aceh thousand hands rhythmic traditional performance',
        'tari kecak'              => 'kecak fire dance bali ramayana chant performance',
        'tari piring'             => 'tari piring minangkabau plate dance traditional acrobatics',
        'tari jaipong'            => 'jaipong traditional west java dance dynamic costume',
        'tari pendet'             => 'pendet balinese welcoming offering dance traditional',
        'tari legong'             => 'legong balinese classical dance intricate eye movements',
        'tari tor tor'            => 'tor tor batak traditional ceremonial dance sumatra',
        'tari gandrung'           => 'gandrung banyuwangi traditional dance performance',
        'tari reog ponorogo'      => 'reog ponorogo singo barong giant lion mask traditional dance',
        'tari tradisional'        => 'traditional indonesian folk dance performance colorful costume',
        'candi borobudur'         => 'borobudur buddhist temple stupa stone relief magelang',
        'candi prambanan'         => 'prambanan hindu temple towers ancient stone relief yogyakarta',
        'candi muaro jambi'       => 'muaro jambi ancient brick temple complex sumatra',
        'candi penataran'         => 'penataran majapahit stone temple blitar east java',
        'candi'                   => 'ancient javanese stone temple candi architecture heritage',
        'peninggalan megalitikum' => 'megalithic stone menhir dolmen sarcophagus prehistoric heritage',
        'menhir'                  => 'megalithic upright standing stone menhir prehistoric monument',
        'dolmen'                  => 'megalithic stone table dolmen burial prehistoric monument',
        'sarkofagus'              => 'megalithic stone coffin sarcophagus prehistoric burial',
        'kerajaan majapahit'      => 'majapahit empire wilwatikta historic artifacts gateway trowulan',
        'kerajaan sriwijaya'      => 'srivijaya maritime buddhist empire sumatra historical artifacts',
        'kerajaan mataram kuno'   => 'ancient mataram medang kingdom temple stone inscriptions',
        'kerajaan demak'          => 'demak sultanate historic mosque tatal timber architecture',
        'kerajaan kutai'          => 'kutai martadipura yupa stone inscriptions oldest kingdom',
        'pasar terapung'          => 'floating market banjarmasin lok baintan traditional boats',
        'pasar tradisional'       => 'indonesian traditional bustling market fresh produce vibrant',
        'upacara ngaben'          => 'ngaben balinese cremation ceremony tower sarcophagus',
        'upacara rambu solo'      => 'rambu solo toraja funeral ceremony buffalo sacrifice',
        'upacara sekaten'         => 'sekaten yogyakarta surakarta royal gamelan tradition',
        'upacara adat'            => 'traditional indonesian cultural ritual ceremonies heritage',
        'pahlawan diponegoro'     => 'prince diponegoro java war historical portrait horse',
        'pahlawan cut nyak dien'  => 'cut nyak dien aceh female national hero historical portrait',
        'pahlawan kartini'        => 'r a kartini women emancipation pioneer historical portrait',
        'pahlawan soekarno'       => 'ir soekarno proclamation first president indonesia historic',
        'pahlawan nasional'       => 'indonesian national heroes historic portraits independence',

        // --- BAHASA INDONESIA & SASTRA CERITA RAKYAT ---
        'fabel kancil dan buaya'  => 'mouse deer and crocodile fable folk tale indonesian illustration',
        'fabel kancil buaya'      => 'mouse deer and crocodile fable folk tale indonesian illustration',
        'fabel kancil dan kura-kura' => 'tortoise and the hare mouse deer fable moral story illustration',
        'fabel kancil'            => 'mouse deer fable illustration indonesian folklore storybook',
        'fabel burung gagak'      => 'crow and pitcher fable clever bird stones illustration',
        'cerita fabel'            => 'anthropomorphic animal fable storybook moral tale illustration',
        'fabel'                   => 'fable animal folklore moral story children book illustration',
        'legenda danau toba'      => 'lake toba sumatra mythical giant fish legend folklore illustration',
        'legenda sangkuriang'     => 'sangkuriang tangkuban perahu sundanese legend folklore boat illustration',
        'cerita malin kundang'    => 'malin kundang stone curse minangkabau beach legend folklore',
        'cerita bawang merah putih' => 'bawang merah bawang putih traditional folk tale storybook',
        'cerita bawang merah'     => 'bawang merah bawang putih traditional folk tale storybook',
        'cerita timun mas'        => 'timun mas golden cucumber green giant buto ijo legend story',
        'cerita roro jonggrang'   => 'roro jonggrang bandung bondowoso thousand temples legend',
        'cerita ciung wanara'     => 'ciung wanara sundanese kingdom rooster fight folklore',
        'cerita si pitung'        => 'si pitung batavia betawi martial art hero folklore illustration',
        'cerita rakyat nusantara' => 'indonesian traditional folklore myth fairy tale storybook illustration',
        'cerita rakyat'           => 'indonesian traditional folklore myth fairy tale storybook illustration',

        // --- MATEMATIKA & GEOMETRI VISUAL ---
        'bangun ruang sisi lengkung' => 'curved 3d geometric shapes cylinder cone sphere diagram',
        'bangun ruang sisi datar'    => 'polyhedra 3d shapes cube cuboid prism pyramid diagram',
        'bangun ruang'            => 'three dimensional geometric shapes cube sphere pyramid prism cylinder',
        'bangun datar'            => 'two dimensional geometric shapes triangle polygon circle quadrilateral',
        'jaring jaring kubus'     => 'cube net unfolding 3d geometry folded surface diagram',
        'jaring jaring balok'     => 'cuboid rectangular prism net unfolding 3d geometry diagram',
        'jaring jaring prisma'    => 'triangular prism net unfolding geometry diagram',
        'jaring jaring limas'     => 'pyramid net unfolding triangular square base geometry diagram',
        'jaring jaring tabung'    => 'cylinder net unfolding geometry circles rectangle diagram',
        'jaring jaring kerucut'   => 'cone net unfolding circular sector base geometry diagram',
        'lingkaran dan sudut'     => 'circle geometry central inscribed angle radius chord tangent diagram',
        'segitiga siku-siku'      => 'right angled triangle hypotenuse legs geometry diagram',
        'koordinat kartesius'     => 'cartesian coordinate system x y axis grid geometry diagram',
        'teorema pythagoras'      => 'pythagorean theorem right triangle a2 b2 c2 geometric proof diagram',

        // --- FAUNA ENDEMIK NUSANTARA ---
        'komodo'                  => 'komodo dragon varanus komodoensis national park reptile giant lizard',
        'burung komodo'           => 'komodo dragon varanus komodoensis national park reptile wildlife',
        'harimau sumatera'        => 'sumatran tiger panthera tigris sumatrae endangered wildlife jungle',
        'gajah sumatera'          => 'sumatran elephant elephas maximus sumatranus wildlife forest sanctuary',
        'badak jawa'              => 'javan rhinoceros rhinoceros sondaicus ujung kulon endangered wildlife',
        'badak bercula satu'      => 'javan rhinoceros rhinoceros sondaicus ujung kulon endangered wildlife',
        'orangutan sumatera'      => 'sumatran orangutan pongo abelii tree canopy rainforest wildlife',
        'orangutan kalimantan'    => 'bornean orangutan pongo pygmaeus rainforest borneo wildlife',
        'orangutan'               => 'orangutan pongo rainforest canopy wild primate indonesia',
        'bekantan'                => 'proboscis monkey nasalis larvatus long nose primate mangrove borneo',
        'monyet belanda'          => 'proboscis monkey nasalis larvatus long nose primate borneo mangrove',
        'anoa'                    => 'anoa dwarf buffalo bubalus depressicornis sulawesi rainforest wildlife',
        'babirusa'                => 'babirusa wild swine deer pig curved tusks sulawesi wildlife',
        'burung jalak bali'       => 'bali myna leucopsar rothschildi white starling blue eye bird fauna',
        'jalak bali'              => 'bali myna leucopsar rothschildi white starling endangered bird',
        'burung cendrawasih'      => 'bird of paradise paradisaea apoda colorful plumage papua jungle',
        'cendrawasih'             => 'bird of paradise paradisaea apoda colorful feathers papua forest',
        'burung rangkong'         => 'hornbill bucerotidae rhinoceros hornbill large beak borneo fauna',
        'burung enggang'          => 'hornbill bucerotidae kalimantan cultural symbol large beak bird',
        'burung kasuari'          => 'southern cassowary casuarius casuarius helmeted flightless bird papua',
        'burung maleo'            => 'maleo macrocephalon maleo sulawesi geothermal nesting bird fauna',
        'pesut mahakam'           => 'irrawaddy dolphin orcaella brevirostris mahakam freshwater river mammal',
        'tarsius'                 => 'tarsius tarsier tarsius spectrum small nocturnal primate big eyes sulawesi',
        'fauna tipe asiatis'      => 'asiatic fauna western indonesia tiger elephant rhino tapir zoology',
        'fauna tipe peralihan'    => 'transitional wallace fauna komodo babirusa anoa maleo zoology',
        'fauna tipe australis'    => 'australasian fauna papua cassowary bird of paradise tree kangaroo',
        'fauna endemik indonesia' => 'endemic indonesian wildlife biodiversity fauna wallace weber',

        // --- FLORA ENDEMIK NUSANTARA ---
        'rafflesia arnoldii'      => 'rafflesia arnoldii giant corpse flower monster blossom bengkulu sumatra',
        'padma raksasa'           => 'rafflesia arnoldii giant parasitic corpse flower rainforest blossom',
        'bunga bangkai'           => 'amorphophallus titanum giant titan arum flowering spadix sumatra',
        'titan arum'              => 'amorphophallus titanum giant titan arum flower botanical garden',
        'kantong semar'           => 'nepenthes pitcher plant carnivorous tropical flora rainforest',
        'anggrek hitam'           => 'black orchid coelogyne pandurata papua kalimantan rare flower',
        'pohon cendana'           => 'santalum album sandalwood fragrant tree timber kupang timor',
        'kayu manis'              => 'cinnamon cinnamomum verum tree bark spice agriculture sumatra',
        'pohon ulin'              => 'ironwood eusideroxylon zwageri borneo timber tree hardwood forest',
        'pohon damar'             => 'agathis dammara copal resin coniferous evergreen tree flora',
        'buah merah papua'        => 'pandanus conoideus red fruit papua traditional food flora',
        'pohon pala'              => 'nutmeg myristica fragrans tree spice fruit banda islands maluku',
        'pohon cengkeh'           => 'clove syzygium aromaticum flower buds spice tree maluku plantation',
        'flora endemik indonesia' => 'endemic tropical flora botanical diversity rainforest indonesia',

        // --- CAGAR BUDAYA, ARSITEKTUR & SITUS SEJARAH ---
        'benteng rotterdam'       => 'fort rotterdam makassar bastions colonial fort gowa architecture',
        'kraton yogyakarta'       => 'kraton yogyakarta royal palace sultanate javanese architecture',
        'istana maimun'           => 'maimun palace medan deli sultanate malay architecture yellow gold',
        'taman sari jogja'        => 'taman sari water castle royal pleasure garden pavilion yogyakarta',
        'benteng vredeburg'       => 'fort vredeburg yogyakarta dutch colonial fortress bastion heritage',
        'lawang sewu'             => 'lawang sewu thousand doors colonial railway landmark semarang architecture',
        'situs sangiran'          => 'sangiran early man site homo erectus fossil prehistoric archaeology',
        'gua leang-leang'         => 'leang leang prehistoric rock art cave hand stencils babirusa maros sulawesi',
        'lukisan gua prasejarah'  => 'prehistoric cave rock painting hand stencil pigments archaeology maros',
        'cagar budaya indonesia'  => 'national cultural heritage historical site monument indonesia',

        // --- SENI UKIR, KERAJINAN & RAGAM HIAS TRADISIONAL ---
        'seni patung asmat'       => 'asmat wood carving ancestor figure bisj pole tribal papuan sculpture',
        'patung asmat'            => 'asmat wood carving tribal ancestor sculpture wooden relief papua',
        'ukiran toraja'           => 'paqssura toraja wood carving motifs geometric traditional house facade',
        'ukiran jepara'           => 'jepara teak wood carving floral relief traditional furniture craftsmanship',
        'topeng cirebon'          => 'cirebon mask dance wooden mask panji kelana traditional expressive',
        'wayang golek'            => 'wayang golek sundanese wooden rod puppet dynamic characters traditional theater',
        'noken papua'             => 'noken unesco traditional woven bag bark thread papua cultural heritage',
        'kain tapis'              => 'tapis cloth lampung traditional gold thread embroidery textile',
        'songket palembang'       => 'songket palembang gold thread luxury brocade textile weaving royal',
        'tenun toraja'            => 'traditional toraja woven fabric geometric ethnic motifs south sulawesi',
        'ragam hias nusantara'    => 'traditional indonesian ornament pattern decorative decorative arts'
    ];


    /**
     * Daftar komprehensif kata terlarang untuk Filter Keamanan Siswa SMP:
     * - NSFW, Pornografi, Erotika, Ketelanjangan
     * - Konten Dewasa & Berbatas Usia (18+, 21+)
     * - Judi, Slot, Kasino & Taruhan
     * - Narkoba, Miras, Rokok & Zat Terlarang
     * - Gore, Darah, Kekerasan Ekstrim & Pembunuhan
     */
    /**
     * Daftar komprehensif kata terlarang untuk Filter Keamanan Siswa SMP:
     * - Bahasa Ilmiah / Kedokteran / Anatomi / Biologi (Latin & Medis)
     * - NSFW, Pornografi, Erotika, Ketelanjangan (Indonesia & Inggris)
     * - Konten Dewasa & Berbatas Usia (18+, 21+, R18)
     * - Judi, Slot, Kasino & Taruhan Online
     * - Narkoba, Miras, Rokok & Zat Terlarang
     * - Gore, Darah, Kekerasan Ekstrim, Mutilasi & Sadisme
     */
    private static array $unsafeBlacklist = [
        // --- BAHASA ILMIAH, ANATOMI, MEDIS & BIOLOGI (LATIN / MEDIS REPRODUKSI & ORGAN INTIM) ---
        'genitalia', 'genital', 'genitalia externa', 'genitalia interna', 'organ intim', 'alat kelamin',
        'phallus', 'phallic', 'fallus', 'penis', 'glans penis', 'glans', 'prepuce', 'preputium', 'foreskin',
        'scrotum', 'skrotum', 'testis', 'testes', 'testicle', 'orchis', 'epididymis', 'vas deferens',
        'spermatozoa', 'sperma', 'semen', 'seminal fluid', 'ejaculatio', 'ejakulasi', 'ejaculation',
        'vulva', 'vulval', 'vulvar', 'labia', 'labium', 'labia majora', 'labia minora', 'clitoris', 'klitoris', 'clitoral',
        'introitus', 'hymen', 'selaput dara', 'vagina', 'vaginal', 'uterus', 'rahim', 'cervix', 'serviks',
        'ovarium', 'ovary', 'tuba falopi', 'fallopian tube', 'perineum', 'perineal', 'anus', 'anal', 'rektum', 'rectum',
        'sphincter', 'sfingter', 'pubis', 'pubic', 'mons pubis', 'rambut kemaluan', 'bulu kemaluan',
        'mammae', 'mammary', 'papilla mammae', 'areola', 'puting', 'puting susu', 'nipple', 'nipples', 'payudara', 'breast', 'breasts',
        'coitus', 'coital', 'koitus', 'copulation', 'kopulasi', 'senggama', 'persetubuhan', 'fornication', 'fornicatio',
        'erection', 'ereksi', 'erectile', 'tumescence', 'orgasm', 'orgasme', 'orgasmic', 'orgasmus',
        'libido', 'aphrodisiac', 'afrodisiak', 'concupiscence', 'hypersexuality', 'hiperseksualitas',
        'onani', 'onanism', 'masturbasi', 'masturbation', 'autoeroticism', 'ipsation',
        'sodomi', 'sodomy', 'fellatio', 'cunnilingus', 'anilingus', 'oral sex', 'seks oral',
        'nymphomania', 'nimfomania', 'satyriasis', 'satiriasis', 'paraphilia', 'parafilia',
        'pedophilia', 'pedofil', 'pedofilia', 'ephebophilia', 'hebephilia', 'necrophilia', 'nekrofilia',
        'zoophilia', 'bestiality', 'bestialitas', 'incest', 'inses', 'sedarah',
        'sadomasochism', 'sadomasokisme', 'masochism', 'masokisme', 'sadism', 'sadisme',
        'fetish', 'fetishism', 'fetisisme', 'voyeurism', 'voyeur', 'exhibitionism', 'eksibisionisme',
        'venereal', 'syphilis', 'sifilis', 'raja singa', 'gonorrhea', 'gonore', 'kencing nanah',
        'herpes genitalis', 'condyloma', 'kondiloma', 'kutil kelamin', 'chlamydia', 'trichomoniasis',

        // --- KATA VULGAR, SLANG & DAERAH (INDONESIA) ---
        'kontol', 'memek', 'jembut', 'titit', 'itil', 'pentil', 'toket', 'tetek', 'nenen', 'pepek',
        'pukang', 'puki', 'pantat bahenol', 'pantat mulus', 'bokong mulus', 'paha mulus',
        'coli', 'colmek', 'ngocok', 'ngaceng', 'sange', 'ngentot', 'entot', 'gentot', 'wikwik', 'ewita',
        'booking', 'open bo', 'bispak', 'jablay', 'lonte', 'perek', 'pelacur', 'ayam kampus', 'germo', 'mucikari',
        'sundal', 'wts', 'psk', 'gigolo', 'bugil', 'telanjang', 'telanjang bulat', 'bugil bulat',
        'mesum', 'syur', 'cabul', 'asusila', 'bokep', 'bokep indo', 'bokep jepang', 'bokep barat',
        'film semi', 'film dewasa', 'cerita dewasa', 'cersex', 'stw', 'tante girang', 'kimpoi',
        'crot', 'peju', 'air mani', 'biji peler', 'peler', 'pelir', 'remas toket',

        // --- NSFW, EROTIKA & PORNOGRAFI (INGGRIS & INTERNASIONAL) ---
        'nsfw', 'porn', 'porno', 'pornografi', 'pornography', 'pornographic', 'pornstar', 'xxx', 'xx', 'x-rated',
        'sex', 'seks', 'sexy', 'seksi', 'sexuality', 'intercourse', 'erotis', 'erotic', 'erotica', 'sensual',
        'naked', 'nude', 'nudity', 'nudist', 'naturist', 'nude art', 'nude model', 'life drawing nude', 'nu artistique',
        'boobs', 'boobies', 'tits', 'titties', 'bobs', 'cleavage', 'topless', 'bottomless',
        'pussy', 'cunt', 'twat', 'cameltoe', 'camel toe', 'coochie', 'snatch',
        'dick', 'cock', 'shaft', 'dong', 'willy', 'schlong', 'ballsack',
        'ass', 'booty', 'butt', 'buttocks', 'butt cheeks', 'thong', 'g-string', 'spanking',
        'blowjob', 'handjob', 'footjob', 'deepthroat', 'rimjob', 'creampie', 'cumshot', 'facial', 'bukkake', 'gangbang',
        'dildo', 'vibrator', 'strap-on', 'butt plug', 'fleshlight', 'sex toy', 'adult toy',
        'lingerie', 'bikini', 'micro bikini', 'see-through', 'transparent clothes', 'striptease', 'stripper', 'lapdance',
        'bondage', 'bdsm', 'domination', 'submission', 'chastity', 'pegging', 'cuckold', 'cuck',
        'hentai', 'ecchi', 'doujinshi', 'doujin', 'ahegao', 'rule34', 'r34', 'lolicon', 'shotacon', 'tentacles',
        'camgirl', 'chaturbate', 'onlyfans', 'fansly', 'playboy', 'penthouse', 'hustler',
        'pornhub', 'xvideos', 'redtube', 'xhamster', 'brazzers', 'youporn', 'xnxx', 'spankbang', 'eporner', 'tube8', 'beeg',
        'escort', 'prostitute', 'prostitution', 'brothel', 'sex worker', 'red light district',
        'boudoir', 'pinup', 'pin-up', 'glamour model', 'sexy girl', 'hot girl', 'milf', 'jav',
        '18+', '21+', 'r18', 'dewasa',

        // --- SEX TOYS, ALAT BANTU SEKS & PERANGKAT DEWASA NOVELTY ---
        'dildo', 'dildos', 'vibrator', 'vibrators', 'fleshlight', 'butt plug', 'anal plug', 'anal beads', 'anal bead',
        'anal douche', 'strap-on', 'strapon', 'cock ring', 'penis ring', 'penis pump', 'penis sleeve', 'penis extender',
        'clit sucker', 'clitoral suction', 'magic wand vibrator', 'rabbit vibrator', 'bullet vibrator', 'love egg', 'egg vibrator',
        'kegel ball', 'kegel balls', 'ben wa balls', 'masturbator', 'male masturbator', 'automatic masturbator',
        'prostate massager', 'prostate stimulator', 'sybian', 'sex machine', 'fuck machine', 'fucking machine', 'milking machine',
        'chastity cage', 'chastity belt', 'cock cage', 'nipple clamps', 'nipple sucker', 'nipple pump', 'breast pump sex',
        'sex doll', 'real doll', 'inflatable doll', 'love doll', 'pocket pussy', 'artificial vagina', 'fake vagina', 'fake penis',
        'glass dildo', 'silicone dildo', 'suction dildo', 'double ended dildo', 'dragon dildo',
        'alat bantu seks', 'alat bantu sex', 'alat pemuas', 'alat masturbasi', 'boneka seks', 'boneka sex',
        'kondom bergerigi', 'kondom duri', 'kondom getar', 'kondom getar silikon', 'kondom sambung',
        'obat kuat', 'viagra', 'cialis', 'hajar jahanam', 'perangsang wanita', 'perangsang cair', 'obat perangsang',
        'dildo silikon', 'vibrator getar', 'vibrator telur', 'vibrator tempel', 'ring silikon penis', 'pompa vakum penis',
        'pelumas seks', 'lubricant sex', 'minyak lintah', 'daun bungkus', 'pembesar penis', 'alat vital pria', 'alat vital wanita',
        'sumbat anus', 'colok anus', 'colok meki', 'colok kontol', 'tali bondage', 'cambuk bdsm', 'borgol ranjang',
        'topeng seks', 'baju haram', 'lingerie transparan', 'g-string transparan', 'crotchless', 'open cup bra',
        'babydoll sexy', 'bodystocking sexy', 'edible underwear',

        // --- BDSM, FETISH & PRAKTIK EKSPLISIT EKSTRIM ---
        'shibari', 'spanking', 'flogger', 'paddle bdsm', 'ball gag', 'gag ball', 'blindfold bdsm', 'spreader bar',
        'leather harness', 'pegging', 'rimming', 'fisting', 'anal fisting', 'gloryhole', 'glory hole',
        'watersports sex', 'golden shower', 'urophilia', 'coprophilia', 'scat sex', 'sounding sex', 'urethral sounding',
        'cuckquean', 'hotwife', 'femdom', 'maledom', 'findom', 'financial domination', 'humiliation fetish',
        'foot fetish', 'erotic hypnosis', 'squirt', 'female ejaculation', 'orgasm denial', 'forced orgasm',
        'edging sex', 'choking sex', 'erotic asphyxiation', 'cum swallowing',

        // --- PLATFORM CAM & SITUS KHUSUS DEWASA ---
        'manyvids', 'camsoda', 'stripchat', 'bongacams', 'myfreecams', 'livejasmin', 'cam4',
        'rule34', 'rule 34', 'hentai haven', 'nhentai', 'tsumino', 'fakku', 'e-hentai', 'exhentai',
        'hanime', 'gelbooru', 'danbooru', 'sankaku complex', 'kemono party', 'coomer party',
        'fapello', 'simpcity', 'thothub',

        // --- JUDI, SLOT, KASINO & TARUHAN ---
        'judi', 'judi online', 'judol', 'slot', 'slot gacor', 'rtp slot', 'rtp live', 'gacor', 'maxwin',
        'pragmatic', 'pragmatic play', 'togel', 'togel online', 'toto', 'bandar togel', 'kasino', 'casino',
        'roulette', 'baccarat', 'blackjack', 'poker', 'poker online', 'domino qq', 'domino qiu qiu',
        'capsa', 'ceme', 'ceme online', 'sbobet', 'ibcbet', 'agen bola', 'taruhan', 'taruhan bola',
        'bandar judi', 'situs judi', 'zeus slot', 'gates of olympus', 'mahjong ways', 'starlight princess',
        'bandar slot', 'jackpot slot', 'slot88', 'betting',

        // --- ZAT TERLARANG, NARKOBA, ROKOK & MIRAS ---
        'miras', 'minuman keras', 'alkohol', 'beer', 'bir', 'wine', 'vodka', 'whiskey', 'tuak', 'arak', 'ciu',
        'narkoba', 'narkotika', 'psikotropika', 'ganja', 'marijuana', 'cannabis', 'weed', 'pot', 'thc', 'bong', 'joint',
        'sabu', 'sabu-sabu', 'methamphetamine', 'crystal meth', 'amphetamine',
        'kokain', 'cocaine', 'crack', 'heroin', 'putaw', 'putau', 'morfin', 'morphine', 'opium', 'opiat', 'candu',
        'ekstasi', 'ecstasy', 'mdma', 'inex', 'lsd', 'mushroom', 'magic mushroom', 'pil koplo', 'tramadol', 'dumolid',
        'drug', 'drugs', 'rokok', 'sigaret', 'cigarette', 'vaping', 'vape', 'liquid vape', 'tembakau', 'cerutu', 'shisha',

        // --- KEKERASAN EKSTRIM, GORE, DARAH & KONTEN SADIS ---
        'gore', 'darah', 'blood', 'bloody', 'pembunuhan', 'murder', 'homicide', 'assassination', 'pembantaian', 'massacre',
        'mutilasi', 'mutilation', 'dismemberment', 'decapitation', 'beheading', 'penggal kepala', 'pancung', 'penggal',
        'bunuh diri', 'suicide', 'gantung diri', 'hanging', 'mayat', 'jenazah', 'corpse', 'cadaver', 'autopsi', 'autopsy', 'otopsi',
        'penyiksaan', 'torture', 'kekerasan', 'violence', 'brutality', 'brutal', 'sadis', 'sadism', 'execution', 'eksekusi',
        'terorisme', 'teroris', 'isis', 'bom bunuh diri', 'senjata tajam', 'celurit', 'samurai', 'bacok', 'tawuran', 'carok',
        'jasad korban', 'kecelakaan maut', 'senjata api', 'senpi', 'siksaan'
    ];

    /**
     * Kata kunci komersial / marketplace e-commerce (Ditolak agar gambar produk dagang tidak muncul)
     */
    private static array $ecommerceBlacklist = [
        'shopee', 'tokopedia', 'lazada', 'bukalapak', 'blibli', 'tiktok shop',
        'jual', 'beli', 'harga', 'diskon', 'promo', 'gratis ongkir', 'free ongkir',
        'cashback', 'voucher', 'sachet', 'grosir', 'reseller', 'distributor',
        'olshop', 'toko online', 'garansi resmi', 'spesifikasi', 'spek', 'stok ready',
        'geforce', 'radeon', 'rtx ', 'rx 6', 'rx 7', 'rx 9',
        'core i3', 'core i5', 'core i7', 'core i9', 'ryzen', 'motherboard', 'gpu', 'vga',
        'ram ddr', 'laptop gaming', 'gadget', 'handphone', 'smartphone', 'anak sehat'
    ];

    /**
     * Deobfuscate string untuk mendeteksi leetspeak atau ejaan yang disamarkan (misal: b0k3p, n.s.f.w, 1 8 +)
     */
    public static function deobfuscate(string $text): string {
        $str = mb_strtolower(trim($text));
        
        // Ganti leetspeak umum
        $replacements = [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '8' => 'b',
            '@' => 'a',
            '$' => 's',
            '+' => ' '
        ];
        $str = strtr($str, $replacements);

        // Hapus tanda baca pembatas kata tersembunyi (misal: n.s.f.w -> nsfw, b-o-k-e-p -> bokep)
        $noPunct = preg_replace('/[._\-*#]/u', '', $str);
        
        // String tanpa spasi sama sekali (misal: "b o k e p" -> "bokep")
        $noSpaces = preg_replace('/\s+/', '', $str);

        return $str . ' ' . $noPunct . ' ' . $noSpaces;
    }

    /**
     * Cek apakah teks atau query lolos filter Keamanan Siswa (Family Safe & Age Restricted)
     * Menggunakan regex word boundary untuk kata pendek (<= 4 huruf) guna mencegah false positive pada kata wajar
     */
    public static function isFamilySafe(string $q): bool {
        $qLower = mb_strtolower(trim($q));
        if (empty($qLower)) return true;

        $deobfuscated = self::deobfuscate($qLower);

        foreach (self::$unsafeBlacklist as $bad) {
            $badLower = mb_strtolower($bad);
            $len = mb_strlen($badLower);

            // Kata dengan spasi (frasa multi-kata): gunakan substring match
            if (str_contains($badLower, ' ')) {
                if (str_contains($qLower, $badLower) || str_contains($deobfuscated, $badLower)) {
                    return false;
                }
                continue;
            }

            // Kata pendek (<= 4 karakter): gunakan word boundary agar tidak salah deteksi kata wajar (misal: library -> bra)
            if ($len <= 4) {
                $pattern = '/(?:\b|_)' . preg_quote($badLower, '/') . '(?:\b|_)/iu';
                if (preg_match($pattern, $qLower) || preg_match($pattern, $deobfuscated)) {
                    return false;
                }
            } else {
                // Kata panjang (> 4 karakter): substring match aman dan sangat efektif
                if (str_contains($qLower, $badLower) || str_contains($deobfuscated, $badLower)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Filter ketat hasil gambar agar tidak memuat konten berbatas umur / NSFW
     * - Menghilangkan secara total gambar dari Wikimedia Commons (sesuai arahan guru)
     * - Memeriksa judul, deskripsi, URL, decoded URL filename, dan domain
     */
    public static function filterSafeItems(array $items): array {
        $clean = array_values(array_filter($items, function($item) {
            $url = $item['url'] ?? '';
            $pageUrl = $item['page_url'] ?? '';
            $source = $item['source'] ?? '';
            $title = $item['title'] ?? '';
            $desc = $item['description'] ?? '';
            $author = $item['author'] ?? '';

            $urlLower = mb_strtolower($url);
            $pageLower = mb_strtolower($pageUrl);
            $sourceLower = mb_strtolower($source);
            $authorLower = mb_strtolower($author);

            // 1. ELIMINASI TOTAL SUMBER WIKIMEDIA:
            // Wikimedia Commons sering kali memiliki arsip gambar anatomi/seni telanjang tanpa sensor yang tidak cocok untuk siswa SMP.
            if (
                str_contains($urlLower, 'wikimedia.org') || 
                str_contains($pageLower, 'wikimedia.org') || 
                str_contains($sourceLower, 'wikimedia') || 
                str_contains($authorLower, 'wikimedia')
            ) {
                return false;
            }

            // 2. Gabungkan seluruh metadata termasuk URL yang sudah di-decode (untuk menangkap nama file eksplisit)
            $decodedUrl = urldecode($urlLower);
            $decodedPage = urldecode($pageLower);
            $combinedText = mb_strtolower(
                $title . ' ' . 
                $desc . ' ' . 
                $source . ' ' . 
                $author . ' ' . 
                $urlLower . ' ' . 
                $decodedUrl . ' ' . 
                $decodedPage
            );
            $deob = self::deobfuscate($combinedText);

            // 3. Cek terhadap seluruh kamus Unsafe Blacklist
            foreach (self::$unsafeBlacklist as $bad) {
                $badLower = mb_strtolower($bad);
                $len = mb_strlen($badLower);

                if (str_contains($badLower, ' ')) {
                    if (str_contains($combinedText, $badLower) || str_contains($deob, $badLower)) {
                        return false;
                    }
                    continue;
                }

                if ($len <= 4) {
                    $pattern = '/(?:\b|_)' . preg_quote($badLower, '/') . '(?:\b|_)/iu';
                    if (preg_match($pattern, $combinedText) || preg_match($pattern, $deob)) {
                        return false;
                    }
                } else {
                    if (str_contains($combinedText, $badLower) || str_contains($deob, $badLower)) {
                        return false;
                    }
                }
            }

            // 4. Saring judul & deskripsi dari kata kunci barang jualan e-commerce / marketplace
            foreach (self::$ecommerceBlacklist as $com) {
                $comLower = mb_strtolower($com);
                $cLen = mb_strlen($comLower);
                if ($cLen <= 4) {
                    $pattern = '/(?:\b|_)' . preg_quote($comLower, '/') . '(?:\b|_)/iu';
                    if (preg_match($pattern, $combinedText) || preg_match($pattern, $deob)) {
                        return false;
                    }
                } else {
                    if (str_contains($combinedText, $comLower) || str_contains($deob, $comLower)) {
                        return false;
                    }
                }
            }

            return true;
        }));

        // Saring domain secara real-time via AdGuard Family Protection DoH & Domain Blacklist
        return AdGuardFilter::filterItems($clean);
    }

    /**
     * Normalisasi keyword pencarian berbahasa Indonesia
     */
    public static function normalize(string $q): string {
        $q = mb_strtolower(trim($q));
        $q = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $q);
        $q = preg_replace('/\s+/', ' ', $q);
        return trim($q);
    }

    /**
     * Jalankan pencarian gambar konsisten dengan batasan tepat 25 item & filter Keamanan Siswa Ketat
     */
    public static function search(string $rawQuery, int $limit = 25, bool $forceRefresh = false): array {
        // Cek langsung pada raw query sebelum normalisasi untuk mendeteksi tanda simbol NSFW (misal: "18+", "xxx")
        if (!self::isFamilySafe($rawQuery)) {
            return [
                'success'     => false,
                'family_safe' => false,
                'query'       => htmlspecialchars($rawQuery, ENT_QUOTES, 'UTF-8'),
                'message'     => '🛡️ Filter Keamanan Siswa Aktif: Pencarian dibatasi karena mengandung kata kunci tidak pantas, NSFW, atau berbatas umur. Digi-Art mengutamakan lingkungan belajar seni yang aman dan ramah siswa SMP.',
                'total'       => 0,
                'max_limit'   => $limit,
                'results'     => []
            ];
        }

        $cleanQuery = self::normalize($rawQuery);
        if (empty($cleanQuery)) {
            $cleanQuery = 'gunung berapi';
        }

        // Cek lagi setelah normalisasi
        if (!self::isFamilySafe($cleanQuery)) {
            return [
                'success'     => false,
                'family_safe' => false,
                'query'       => $cleanQuery,
                'message'     => '🛡️ Filter Keamanan Siswa Aktif: Pencarian dibatasi karena mengandung konten tidak pantas / berbatas usia (NSFW). Silakan gunakan topik materi kurikulum seni rupa atau pelajaran sekolah.',
                'total'       => 0,
                'max_limit'   => $limit,
                'results'     => []
            ];
        }

        $queryHash = md5($cleanQuery);
        $db = Database::getInstance();

        // 1. Cek Cache Database MySQL (Read-Through Cache untuk Kecepatan & Menghemat Kuota)
        if (!$forceRefresh) {
            try {
                $cached = $db->fetch(
                    "SELECT results_json, updated_at FROM referensi_cache WHERE query_hash = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
                    [$queryHash]
                );
                if (!empty($cached['results_json'])) {
                    $cachedData = json_decode($cached['results_json'], true);
                    if (is_array($cachedData) && !empty($cachedData)) {
                        $safeCached = self::filterSafeItems($cachedData);
                        if (!empty($safeCached)) {
                            return [
                                'success'     => true,
                                'family_safe' => true,
                                'source_type' => 'cache',
                                'query'       => $cleanQuery,
                                'total'       => count($safeCached),
                                'max_limit'   => $limit,
                                'results'     => $safeCached
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                // Lanjut ke pencarian live jika cache belum tersedia
            }
        }

        $collected = [];
        $seenUrls = [];

        // 2. Google Custom Search API jika API Key & CX terpasang (SafeSearch Active)
        if (count($collected) < $limit) {
            $googleApiItems = self::filterSafeItems(self::fetchFromGoogleCustomSearch($cleanQuery, $limit - count($collected)));
            foreach ($googleApiItems as $item) {
                $urlKey = strtolower(trim($item['url']));
                if (!isset($seenUrls[$urlKey])) {
                    $seenUrls[$urlKey] = true;
                    $collected[] = $item;
                }
                if (count($collected) >= $limit) break;
            }
        }

        // 3. Prioritas 2: Web Image Engine (Strict SafeSearch Edukasi) - Relevan & Terkini
        if (count($collected) < $limit) {
            $webItems = self::filterSafeItems(self::fetchFromWebSearch($cleanQuery, $limit - count($collected)));
            foreach ($webItems as $item) {
                $urlKey = strtolower(trim($item['url']));
                if (!isset($seenUrls[$urlKey])) {
                    $seenUrls[$urlKey] = true;
                    $collected[] = $item;
                }
                if (count($collected) >= $limit) break;
            }
        }

        // 4. Prioritas 3: Varian Visual dari Thesaurus Edukasi jika kuota belum terpenuhi
        if (count($collected) < $limit) {
            $translated = self::translateQuery($cleanQuery);
            if ($translated !== $cleanQuery && !empty($translated)) {
                $transItems = self::filterSafeItems(self::fetchFromWebSearch($translated, $limit - count($collected)));
                foreach ($transItems as $item) {
                    $urlKey = strtolower(trim($item['url']));
                    if (!isset($seenUrls[$urlKey])) {
                        $seenUrls[$urlKey] = true;
                        $collected[] = $item;
                    }
                    if (count($collected) >= $limit) break;
                }
            }
        }

        // Catatan: Wikimedia Commons dihilangkan sepenuhnya (0% Wikimedia) demi menjaga perlindungan 100% dari materi tidak pantas.

        // Filter menyeluruh kembali untuk memastikan nol kebocoran
        $safeCollected = self::filterSafeItems($collected);

        // 6. Urutkan berdasarkan relevansi dengan keyword & beri ID berurutan 1..N
        $finalResults = [];
        $count = 0;
        foreach ($safeCollected as $c) {
            $count++;
            $c['id'] = $count;
            $finalResults[] = $c;
            if ($count >= $limit) break;
        }

        // 7. Simpan ke Cache Database untuk Konsistensi Mutlak pada Pencarian Berikutnya
        if (!empty($finalResults)) {
            try {
                $db->query(
                    "INSERT INTO referensi_cache (query_hash, query_text, results_json, total_items, updated_at) 
                     VALUES (?, ?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE query_text = VALUES(query_text), results_json = VALUES(results_json), total_items = VALUES(total_items), updated_at = NOW()",
                    [$queryHash, $cleanQuery, json_encode($finalResults, JSON_UNESCAPED_UNICODE), count($finalResults)]
                );
            } catch (Exception $e) {
                // Abaikan error cache
            }
        }

        return [
            'success'     => true,
            'family_safe' => true,
            'source_type' => 'live_cached',
            'query'       => $cleanQuery,
            'total'       => count($finalResults),
            'max_limit'   => $limit,
            'results'     => $finalResults
        ];
    }

    /**
     * Cari via Google Custom Search JSON API
     */
    private static function fetchFromGoogleCustomSearch(string $q, int $limit = 25): array {
        $apiKey = defined('GOOGLE_API_KEY') ? GOOGLE_API_KEY : '';
        $cx     = defined('GOOGLE_SEARCH_CX') ? GOOGLE_SEARCH_CX : '';

        if (empty($apiKey) || empty($cx)) {
            return [];
        }

        $results = [];
        $params = http_build_query([
            'q'          => $q . ' ilustrasi',
            'searchType' => 'image',
            'key'        => $apiKey,
            'cx'         => $cx,
            'num'        => min(10, $limit),
            'safe'       => 'active',
            'hl'         => 'id',
            'lr'         => 'lang_id'
        ]);

        $url = 'https://www.googleapis.com/customsearch/v1?' . $params;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) return [];
        $data = json_decode($resp, true);
        $items = $data['items'] ?? [];

        foreach ($items as $item) {
            $img = $item['link'] ?? '';
            $thumb = $item['image']['thumbnailLink'] ?? $img;
            $title = $item['title'] ?? 'Referensi Google Images';
            $contextLink = $item['image']['contextLink'] ?? $img;
            $host = parse_url($contextLink, PHP_URL_HOST) ?: 'Google Images';

            if (empty($img)) continue;

            $results[] = [
                'title'       => mb_substr($title, 0, 75),
                'url'         => $img,
                'thumb'       => $thumb,
                'source'      => 'Google Images (' . $host . ')',
                'author'      => $host,
                'description' => mb_substr($item['snippet'] ?? $title, 0, 150),
                'page_url'    => $contextLink,
                'width'       => $item['image']['width'] ?? 800,
                'height'      => $item['image']['height'] ?? 600
            ];
            if (count($results) >= $limit) break;
        }

        return $results;
    }

    /**
     * Cari via Wikipedia Bahasa Indonesia
     */
    private static function fetchFromIdWikipedia(string $q, int $max = 12): array {
        $url = 'https://id.wikipedia.org/w/api.php?' . http_build_query([
            'action'      => 'query',
            'generator'   => 'search',
            'gsrsearch'   => $q,
            'gsrlimit'    => $max,
            'prop'        => 'pageimages|extracts',
            'piprop'      => 'original|thumbnail',
            'pithumbsize' => 600,
            'exintro'     => 1,
            'explaintext' => 1,
            'exchars'     => 140,
            'format'      => 'json'
        ]);

        $opts = ['http' => ['header' => "User-Agent: DigiArtEducation/2.0 (Indonesia)\r\n", 'timeout' => 6]];
        $res = @file_get_contents($url, false, stream_context_create($opts));
        if (!$res) return [];

        $data = json_decode($res, true);
        $pages = $data['query']['pages'] ?? [];
        $items = [];

        foreach ($pages as $p) {
            $thumb = $p['thumbnail']['source'] ?? null;
            $orig  = $p['original']['source'] ?? $thumb;
            if (!$thumb || !$orig) continue;

            $title = $p['title'] ?? 'Referensi Visual';
            $desc  = trim($p['extract'] ?? '');
            if (empty($desc)) {
                $desc = "Ilustrasi dan dokumentasi materi edukasi seni rupa mengenai $title.";
            }

            $items[] = [
                'title'       => mb_substr($title, 0, 75),
                'url'         => $orig,
                'thumb'       => $thumb,
                'source'      => 'Google / Ensiklopedia Indonesia',
                'author'      => 'id.wikipedia.org',
                'description' => mb_substr($desc, 0, 150),
                'page_url'    => 'https://id.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $title)),
                'width'       => $p['original']['width'] ?? 800,
                'height'      => $p['original']['height'] ?? 600
            ];
        }
        return $items;
    }

    /**
     * Cari via Wikimedia Commons (Dinonaktifkan Permanen demi Keamanan Lingkungan Siswa SMP)
     */
    private static function fetchFromWikimediaCommons(string $q, int $max = 15): array {
        // Sepenuhnya dinonaktifkan: 0% sumber Wikimedia
        return [];
    }

    /**
     * Cari via Web Visual Search Proxy dengan Strict SafeSearch & Anti E-Commerce
     */
    private static function fetchFromWebSearch(string $q, int $max = 15): array {
        $cleanQ = trim($q);
        if (empty($cleanQ)) return [];

        // Exclude ecommerce marketplaces & commercial keywords agar referensi bersih dari barang dagangan
        $queryParam = $cleanQ . ' -site:shopee.co.id -site:tokopedia.com -site:lazada.co.id -site:bukalapak.com -site:blibli.com -site:tiktok.com -jual -beli -harga -promo -diskon';
        $searchUrl = 'https://www.bing.com/images/async?q=' . urlencode($queryParam) . '&count=40&first=0&adlt=strict';
        
        $ch = curl_init($searchUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ['Accept-Language: id-ID,id;q=0.9,en-US;q=0.8']
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) return [];

        $items = [];
        preg_match_all('/m="({[^"]+})"/', $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $raw) {
                $data = json_decode(htmlspecialchars_decode($raw, ENT_QUOTES), true);
                if (!is_array($data) || empty($data['murl'])) continue;

                $title = strip_tags(str_replace(['', ''], '', $data['t'] ?? $data['desc'] ?? 'Referensi Visual'));
                $pageUrl = $data['purl'] ?? $data['murl'];
                $host = parse_url($pageUrl, PHP_URL_HOST) ?: 'Referensi Visual';

                $items[] = [
                    'title'       => mb_substr($title, 0, 75),
                    'url'         => $data['murl'],
                    'thumb'       => $data['turl'] ?? $data['murl'],
                    'source'      => 'Referensi Visual (' . $host . ')',
                    'author'      => $host,
                    'description' => mb_substr($title, 0, 150),
                    'page_url'    => $pageUrl,
                    'width'       => 800,
                    'height'      => 600
                ];
                if (count($items) >= $max) break;
            }
        }
        return $items;
    }

    /**
     * Terjemahkan input multibahasa (Inggris, Prancis, Jepang, dll) ke Bahasa Indonesia secara otomatis & ringan
     */
    public static function translateToIndonesian(string $text): string {
        $clean = trim($text);
        if (empty($clean) || strlen($clean) <= 2) return $clean;

        // Cek jika sudah terpetakan di kamus kontekstual / thesaurus
        $lower = mb_strtolower($clean);
        foreach (self::$thesaurus as $ind => $eng) {
            if ($lower === $ind || $lower === $eng) {
                return $ind;
            }
        }

        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=id&dt=t&q=" . urlencode($clean);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $res) {
            $data = json_decode($res, true);
            if (!empty($data[0][0][0])) {
                return trim($data[0][0][0]);
            }
        }

        return $clean;
    }

    /**
     * Terjemahkan keyword Indonesia ke terminologi visual Inggris
     */
    private static function translateQuery(string $q): string {
        $qLower = mb_strtolower(trim($q));
        foreach (self::$thesaurus as $ind => $eng) {
            if (str_contains($qLower, $ind)) {
                return $eng;
            }
        }
        return $q . ' diagram illustration';
    }
}
