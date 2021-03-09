// Was sollte Installiert/Vorhanden sein
- Font Awesome Icons

// neue DB
- relationen

//Templates
relationen 	
relationen_alert 	
relationen_anfragen 	
relationen_anfragen_bit 	
relationen_bit_profil 	
relationen_bit_profil_edit 	
relationen_bit_profil_edit_npc 	
relationen_formular 	
relationen_formular_npc 	
relationen_profil_bit

//CSS
relationen.css

//Pfad fürs usercp 
usercp.php?action=relationen

//variabeln
Header: {$relationen_alert}
member_profile: {$relationen}

// Was sollte angepasst werden
# Suche 
     //Shortfacts kannst du hier eingeben. Hierzu kannst du jegliche Profilfelder in der form $row['fidxx'] einfügen.
                $shortfacts = "Hier fehlen noch die Shortfacts in der PHP.";
 - Wenn du Profilfelder Auslesen möchtest, Entferne "Hier fehlen noch die Shortfacts in der PHP." und ersetze es mit $row['fidxx']. Für xx fügst du die FID das Profilfeldes ein. 
   Möchtest du mehrere punkte zusammenfügen, kannst du das entweder über . machen, also $row['fidxx'].$row['fidxx] und wenn du Wörter dazwischen packen möchtest oder Zeichen, sieht das so aus
   $row['fidxx']." # ".$row['fidxx']. NPCs haben ein Shortfactfeld.
