<?php
session_start();
require_once('../config/db.php');

// Redirect if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get user info and wallet balance
$stmt = $conn->prepare("SELECT full_name, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $wallet_balance);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register CAC | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .single-card {
      max-width: 600px;
      margin: 2rem auto;
      padding: 2rem;
      border: 2px solid #ccc;
      border-radius: 8px;
      background: #f9f9f9;
      text-align: center;
    }
    .cac-form label { display: block; margin-top: 1rem; text-align: left; }
    .cac-form input, .cac-form select {
      width: 100%; padding: 0.5rem; margin-top: 0.3rem; border-radius: 4px; border: 1px solid #ccc;
    }
    .cac-form button { margin-top: 1.5rem; }
    .modification-price {
      margin: 1rem 0;
      font-weight: bold;
      color: #007bff;
    }
    .form-section {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 1.2rem 1rem 0.5rem 1rem;
      margin-bottom: 1.5rem;
      text-align: left;
    }
    .form-section h4 {
      margin-top: 0;
      color: #067c3c;
      font-size: 1.1rem;
      margin-bottom: 0.7rem;
      font-weight: 600;
    }
    .cac-form label {
      margin-top: 0.7rem;
      margin-bottom: 0.2rem;
      font-size: 0.98rem;
    }
    .cac-form input, .cac-form select {
      margin-bottom: 0.3rem;
    }
    .cac-form button[type="submit"] {
      margin-top: 1.5rem;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- ...existing code for sidebar and header... -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <section class="service-header">
        <div class="header-content">
          <h2>Register CAC Business Name</h2>
          <p>Fill the form below to register your business name on CAC</p>
          <div class="modification-price" style="margin-top:0.7rem;font-size:1.08rem;">
            <i class="fas fa-money-bill-wave"></i> Service Price: <strong>₦20,000</strong>
          </div>
        </div>
        <div class="service-icon">
          <i class="fas fa-briefcase"></i>
        </div>
      </section>

      <div class="single-card">
        <form class="cac-form" method="post" action="process_register_cac.php" enctype="multipart/form-data">
          <div class="form-section">
            <h4>Personal Details</h4>
            <label>Surname <input type="text" name="surname" required></label>
            <label>First Name <input type="text" name="first_name" required></label>
            <label>Other Name <input type="text" name="other_name"></label>
            <label>Date of Birth <input type="date" name="dob" required></label>
            <label>Gender
              <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </label>
            <label>Phone Number <input type="tel" name="phone" required></label>
          </div>

          <div class="form-section">
            <h4>Home Address</h4>
            <label>State
              <select name="home_state" id="home_state" required onchange="populateLGA('home_state','home_lga')">
                <option value="">Select State</option>
              </select>
            </label>
            <label>LGA
              <select name="home_lga" id="home_lga" required>
                <option value="">Select LGA</option>
              </select>
            </label>
            <label>City/Town/Village <input type="text" name="home_city" required></label>
            <label>House Number <input type="text" name="home_house_number" required></label>
            <label>Street Name <input type="text" name="home_street" required></label>
          </div>

          <div class="form-section">
            <h4>Business Address</h4>
            <label>State
              <select name="biz_state" id="biz_state" required onchange="populateLGA('biz_state','biz_lga')">
                <option value="">Select State</option>
              </select>
            </label>
            <label>LGA
              <select name="biz_lga" id="biz_lga" required>
                <option value="">Select LGA</option>
              </select>
            </label>
            <label>City/Town/Village <input type="text" name="biz_city" required></label>
            <label>House Number <input type="text" name="biz_house_number" required></label>
            <label>Street Name <input type="text" name="biz_street" required></label>
          </div>

          <div class="form-section">
            <h4>Business Details</h4>
            <label>Nature of Business <input type="text" name="nature_of_business" required></label>
            <label>Business Name 1 <input type="text" name="business_name1" required></label>
            <label>Business Name 2 <input type="text" name="business_name2"></label>
            <label>Functional Email Address <input type="email" name="email" required></label>
          </div>

          <div class="form-section">
            <h4>Supporting Documents</h4>
            <label>ID Card (snap and send) <input type="file" name="id_card" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <label>Passport (snap and send) <input type="file" name="passport" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <label>Signature (sign on paper, snap and send) <input type="file" name="signature" accept=".jpg,.jpeg,.png,.pdf" required></label>
          </div>

          <button class="btn btn-primary" type="submit" style="margin-top:1.5rem;">Submit Registration</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    // Show popup alert if CAC registration was successful
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        showSuccessPopup("Your CAC registration request has been submitted. Our team will process it shortly.");
      }
    });

    function showSuccessPopup(message) {
      let popup = document.createElement('div');
      popup.style.position = 'fixed';
      popup.style.top = '30px';
      popup.style.left = '50%';
      popup.style.transform = 'translateX(-50%)';
      popup.style.background = '#067c3c';
      popup.style.color = '#fff';
      popup.style.padding = '1rem 2.2rem';
      popup.style.borderRadius = '8px';
      popup.style.boxShadow = '0 4px 24px rgba(0,0,0,0.13)';
      popup.style.fontSize = '1.08rem';
      popup.style.zIndex = '99999';
      popup.style.textAlign = 'center';
      popup.innerHTML = `<span style="margin-right:0.7rem;"><i class="fas fa-check-circle"></i></span>${message}`;
      document.body.appendChild(popup);
      setTimeout(() => {
        popup.style.transition = 'opacity 0.4s';
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 400);
      }, 3200);
    }

    // Complete Nigeria states and LGAs data
    const statesAndLGAs = {
      "Abia": [
        "Aba North", "Aba South", "Arochukwu", "Bende", "Ikwuano", "Isiala Ngwa North", "Isiala Ngwa South", "Isuikwuato", "Obi Ngwa", "Ohafia", "Osisioma", "Ugwunagbo", "Ukwa East", "Ukwa West", "Umuahia North", "Umuahia South", "Umu Nneochi"
      ],
      "Adamawa": [
        "Demsa", "Fufore", "Ganye", "Girei", "Gombi", "Guyuk", "Hong", "Jada", "Lamurde", "Madagali", "Maiha", "Mayo-Belwa", "Michika", "Mubi North", "Mubi South", "Numan", "Shelleng", "Song", "Toungo", "Yola North", "Yola South"
      ],
      "Akwa Ibom": [
        "Abak", "Eastern Obolo", "Eket", "Esit Eket", "Essien Udim", "Etim Ekpo", "Etinan", "Ibeno", "Ibesikpo Asutan", "Ibiono-Ibom", "Ika", "Ikono", "Ikot Abasi", "Ikot Ekpene", "Ini", "Itu", "Mbo", "Mkpat-Enin", "Nsit-Atai", "Nsit-Ibom", "Nsit-Ubium", "Obot Akara", "Okobo", "Onna", "Oron", "Oruk Anam", "Udung-Uko", "Ukanafun", "Uruan", "Urue-Offong/Oruko", "Uyo"
      ],
      "Anambra": [
        "Aguata", "Anambra East", "Anambra West", "Anaocha", "Awka North", "Awka South", "Ayamelum", "Dunukofia", "Ekwusigo", "Idemili North", "Idemili South", "Ihiala", "Njikoka", "Nnewi North", "Nnewi South", "Ogbaru", "Onitsha North", "Onitsha South", "Orumba North", "Orumba South", "Oyi"
      ],
      "Bauchi": [
        "Alkaleri", "Bauchi", "Bogoro", "Damban", "Darazo", "Dass", "Gamawa", "Ganjuwa", "Giade", "Itas/Gadau", "Jama'are", "Katagum", "Kirfi", "Misau", "Ningi", "Shira", "Tafawa Balewa", "Toro", "Warji", "Zaki"
      ],
      "Bayelsa": [
        "Brass", "Ekeremor", "Kolokuma/Opokuma", "Nembe", "Ogbia", "Sagbama", "Southern Ijaw", "Yenagoa"
      ],
      "Benue": [
        "Ado", "Agatu", "Apa", "Buruku", "Gboko", "Guma", "Gwer East", "Gwer West", "Katsina-Ala", "Konshisha", "Kwande", "Logo", "Makurdi", "Obi", "Ogbadibo", "Ohimini", "Oju", "Okpokwu", "Otukpo", "Tarka", "Ukum", "Ushongo", "Vandeikya"
      ],
      "Borno": [
        "Abadam", "Askira/Uba", "Bama", "Bayo", "Biu", "Chibok", "Damboa", "Dikwa", "Gubio", "Guzamala", "Gwoza", "Hawul", "Jere", "Kaga", "Kala/Balge", "Konduga", "Kukawa", "Kwaya Kusar", "Mafa", "Magumeri", "Maiduguri", "Marte", "Mobbar", "Monguno", "Ngala", "Nganzai", "Shani"
      ],
      "Cross River": [
        "Abi", "Akamkpa", "Akpabuyo", "Bakassi", "Bekwarra", "Biase", "Boki", "Calabar Municipal", "Calabar South", "Etung", "Ikom", "Obanliku", "Obubra", "Obudu", "Odukpani", "Ogoja", "Yakurr", "Yala"
      ],
      "Delta": [
        "Aniocha North", "Aniocha South", "Bomadi", "Burutu", "Ethiope East", "Ethiope West", "Ika North East", "Ika South", "Isoko North", "Isoko South", "Ndokwa East", "Ndokwa West", "Okpe", "Oshimili North", "Oshimili South", "Patani", "Sapele", "Udu", "Ughelli North", "Ughelli South", "Ukwuani", "Uvwie", "Warri North", "Warri South", "Warri South West"
      ],
      "Ebonyi": [
        "Abakaliki", "Afikpo North", "Afikpo South", "Ebonyi", "Ezza North", "Ezza South", "Ikwo", "Ishielu", "Ivo", "Izzi", "Ohaozara", "Ohaukwu", "Onicha"
      ],
      "Edo": [
        "Akoko-Edo", "Egor", "Esan Central", "Esan North-East", "Esan South-East", "Esan West", "Etsako Central", "Etsako East", "Etsako West", "Igueben", "Ikpoba-Okha", "Oredo", "Orhionmwon", "Ovia North-East", "Ovia South-West", "Owan East", "Owan West", "Uhunmwonde"
      ],
      "Ekiti": [
        "Ado Ekiti", "Efon", "Ekiti East", "Ekiti South-West", "Ekiti West", "Emure", "Gbonyin", "Ido Osi", "Ijero", "Ikere", "Ikole", "Ilejemeje", "Irepodun/Ifelodun", "Ise/Orun", "Moba", "Oye"
      ],
      "Enugu": [
        "Aninri", "Awgu", "Enugu East", "Enugu North", "Enugu South", "Ezeagu", "Igbo Etiti", "Igbo Eze North", "Igbo Eze South", "Isi Uzo", "Nkanu East", "Nkanu West", "Nsukka", "Oji River", "Udenu", "Udi", "Uzo Uwani"
      ],
      "FCT": [
        "Abaji", "Bwari", "Gwagwalada", "Kuje", "Kwali", "Municipal"
      ],
      "Gombe": [
        "Akko", "Balanga", "Billiri", "Dukku", "Funakaye", "Gombe", "Kaltungo", "Kwami", "Nafada", "Shongom", "Yamaltu/Deba"
      ],
      "Imo": [
        "Aboh Mbaise", "Ahiazu Mbaise", "Ehime Mbano", "Ezinihitte", "Ideato North", "Ideato South", "Ihitte/Uboma", "Ikeduru", "Isiala Mbano", "Isu", "Mbaitoli", "Ngor Okpala", "Njaba", "Nkwerre", "Nwangele", "Obowo", "Oguta", "Ohaji/Egbema", "Okigwe", "Onuimo", "Orlu", "Orsu", "Oru East", "Oru West", "Owerri Municipal", "Owerri North", "Owerri West"
      ],
      "Jigawa": [
        "Auyo", "Babura", "Biriniwa", "Birnin Kudu", "Buji", "Dutse", "Gagarawa", "Garki", "Gumel", "Guri", "Gwaram", "Gwiwa", "Hadejia", "Jahun", "Kafin Hausa", "Kaugama", "Kazaure", "Kiri Kasama", "Kiyawa", "Maigatari", "Malam Madori", "Miga", "Ringim", "Roni", "Sule Tankarkar", "Taura", "Yankwashi"
      ],
      "Kaduna": [
        "Birnin Gwari", "Chikun", "Giwa", "Igabi", "Ikara", "Jaba", "Jema'a", "Kachia", "Kaduna North", "Kaduna South", "Kagarko", "Kajuru", "Kaura", "Kauru", "Kubau", "Kudan", "Lere", "Makarfi", "Sabon Gari", "Sanga", "Soba", "Zangon Kataf", "Zaria"
      ],
      "Kano": [
        "Ajingi", "Albasu", "Bagwai", "Bebeji", "Bichi", "Bunkure", "Dala", "Dambatta", "Dawakin Kudu", "Dawakin Tofa", "Doguwa", "Fagge", "Gabasawa", "Garko", "Garun Mallam", "Gaya", "Gezawa", "Gwale", "Gwarzo", "Kabo", "Kano Municipal", "Karaye", "Kibiya", "Kiru", "Kumbotso", "Kunchi", "Kura", "Madobi", "Makoda", "Minjibir", "Nasarawa", "Rano", "Rimin Gado", "Rogo", "Shanono", "Sumaila", "Takai", "Tarauni", "Tofa", "Tsanyawa", "Tudun Wada", "Ungogo", "Warawa", "Wudil"
      ],
      "Katsina": [
        "Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dandume", "Danja", "Dan Musa", "Daura", "Dutsi", "Dutsin-Ma", "Faskari", "Funtua", "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Katsina", "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Matazu", "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"
      ],
      "Kebbi": [
        "Aleiro", "Arewa Dandi", "Argungu", "Augie", "Bagudo", "Birnin Kebbi", "Bunza", "Dandi", "Fakai", "Gwandu", "Jega", "Kalgo", "Koko/Besse", "Maiyama", "Ngaski", "Sakaba", "Shanga", "Suru", "Wasagu/Danko", "Yauri", "Zuru"
      ],
      "Kogi": [
        "Adavi", "Ajaokuta", "Ankpa", "Bassa", "Dekina", "Ibaji", "Idah", "Igalamela Odolu", "Ijumu", "Kabba/Bunu", "Kogi", "Lokoja", "Mopa-Muro", "Ofu", "Ogori/Magongo", "Okehi", "Okene", "Olamaboro", "Omala", "Yagba East", "Yagba West"
      ],
      "Kwara": [
        "Asa", "Baruten", "Edu", "Ekiti", "Ifelodun", "Ilorin East", "Ilorin South", "Ilorin West", "Irepodun", "Isin", "Kaiama", "Moro", "Offa", "Oke Ero", "Oyun", "Pategi"
      ],
      "Lagos": [
        "Agege", "Ajeromi-Ifelodun", "Alimosho", "Amuwo-Odofin", "Apapa", "Badagry", "Epe", "Eti Osa", "Ibeju-Lekki", "Ifako-Ijaiye", "Ikeja", "Ikorodu", "Kosofe", "Lagos Island", "Lagos Mainland", "Mushin", "Ojo", "Oshodi-Isolo", "Shomolu", "Surulere"
      ],
      "Nasarawa": [
        "Akwanga", "Awe", "Doma", "Karu", "Keana", "Keffi", "Kokona", "Lafia", "Nasarawa", "Nasarawa Egon", "Obi", "Toto", "Wamba"
      ],
      "Niger": [
        "Agaie", "Agwara", "Bida", "Borgu", "Bosso", "Chanchaga", "Edati", "Gbako", "Gurara", "Katcha", "Kontagora", "Lapai", "Lavun", "Magama", "Mariga", "Mashegu", "Mokwa", "Moya", "Paikoro", "Rafi", "Rijau", "Shiroro", "Suleja", "Tafa", "Wushishi"
      ],
      "Ogun": [
        "Abeokuta North", "Abeokuta South", "Ado-Odo/Ota", "Egbado North", "Egbado South", "Ewekoro", "Ifo", "Ijebu East", "Ijebu North", "Ijebu North East", "Ijebu Ode", "Ikenne", "Imeko Afon", "Ipokia", "Obafemi Owode", "Odeda", "Odogbolu", "Ogun Waterside", "Remo North", "Shagamu"
      ],
      "Ondo": [
        "Akoko North-East", "Akoko North-West", "Akoko South-East", "Akoko South-West", "Akure North", "Akure South", "Ese Odo", "Idanre", "Ifedore", "Ilaje", "Ile Oluji/Okeigbo", "Irele", "Odigbo", "Okitipupa", "Ondo East", "Ondo West", "Ose", "Owo"
      ],
      "Osun": [
        "Aiyedaade", "Aiyedire", "Atakumosa East", "Atakumosa West", "Boluwaduro", "Boripe", "Ede North", "Ede South", "Egbedore", "Ejigbo", "Ife Central", "Ife East", "Ife North", "Ife South", "Ifedayo", "Ifelodun", "Ila", "Ilesa East", "Ilesa West", "Irepodun", "Irewole", "Isokan", "Iwo", "Obokun", "Odo Otin", "Ola Oluwa", "Olorunda", "Oriade", "Orolu", "Osogbo"
      ],
      "Oyo": [
        "Afijio", "Akinyele", "Atiba", "Atisbo", "Egbeda", "Ibadan North", "Ibadan North-East", "Ibadan North-West", "Ibadan South-East", "Ibadan South-West", "Ibarapa Central", "Ibarapa East", "Ibarapa North", "Ido", "Irepo", "Iseyin", "Itesiwaju", "Iwajowa", "Kajola", "Lagelu", "Ogbomosho North", "Ogbomosho South", "Ogo Oluwa", "Olorunsogo", "Oluyole", "Ona Ara", "Orelope", "Ori Ire", "Oyo East", "Oyo West", "Saki East", "Saki West", "Surulere"
      ],
      "Plateau": [
        "Barkin Ladi", "Bassa", "Bokkos", "Jos East", "Jos North", "Jos South", "Kanam", "Kanke", "Langtang North", "Langtang South", "Mangu", "Mikang", "Pankshin", "Qua'an Pan", "Riyom", "Shendam", "Wase"
      ],
      "Rivers": [
        "Abua/Odual", "Ahoada East", "Ahoada West", "Akuku-Toru", "Andoni", "Asari-Toru", "Bonny", "Degema", "Eleme", "Emohua", "Etche", "Gokana", "Ikwerre", "Khana", "Obio/Akpor", "Ogba/Egbema/Ndoni", "Ogu/Bolo", "Okrika", "Omuma", "Opobo/Nkoro", "Oyigbo", "Port Harcourt", "Tai"
      ],
      "Sokoto": [
        "Binji", "Bodinga", "Dange Shuni", "Gada", "Goronyo", "Gudu", "Gwadabawa", "Illela", "Isa", "Kebbe", "Kware", "Rabah", "Sabon Birni", "Shagari", "Silame", "Sokoto North", "Sokoto South", "Tambuwal", "Tangaza", "Tureta", "Wamako", "Wurno", "Yabo"
      ],
      "Taraba": [
        "Ardo Kola", "Bali", "Donga", "Gashaka", "Gassol", "Ibi", "Jalingo", "Karim Lamido", "Kumi", "Lau", "Sardauna", "Takum", "Ussa", "Wukari", "Yorro", "Zing"
      ],
      "Yobe": [
        "Bade", "Bursari", "Damaturu", "Fika", "Fune", "Geidam", "Gujba", "Gulani", "Jakusko", "Karasuwa", "Machina", "Nangere", "Nguru", "Potiskum", "Tarmuwa", "Yunusari", "Yusufari"
      ],
      "Zamfara": [
        "Anka", "Bakura", "Birnin Magaji", "Bukkuyum", "Bungudu", "Gummi", "Gusau", "Kaura Namoda", "Maradun", "Maru", "Shinkafi", "Talata Mafara", "Chafe", "Zurmi"
      ]
    };

    function populateStates(selectId) {
      const select = document.getElementById(selectId);
      select.innerHTML = '<option value="">Select State</option>';
      Object.keys(statesAndLGAs).forEach(state => {
        const opt = document.createElement('option');
        opt.value = state;
        opt.textContent = state;
        select.appendChild(opt);
      });
    }

    function populateLGA(stateSelectId, lgaSelectId) {
      const state = document.getElementById(stateSelectId).value;
      const lgaSelect = document.getElementById(lgaSelectId);
      lgaSelect.innerHTML = '<option value="">Select LGA</option>';
      if (statesAndLGAs[state]) {
        statesAndLGAs[state].forEach(lga => {
          const opt = document.createElement('option');
          opt.value = lga;
          opt.textContent = lga;
          lgaSelect.appendChild(opt);
        });
      }
    }

    // Initialize state selects on page load
    document.addEventListener('DOMContentLoaded', function() {
      populateStates('home_state');
      populateStates('biz_state');
    });

    // ...existing code...
  </script>
  <script src="../js/script.js"></script>
  <?php include __DIR__ . '/../includes/spinner.php'; ?>
  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
