DROP DATABASE IF EXISTS med_vrsticam;
CREATE DATABASE IF NOT EXISTS med_vrsticam;

USE med_vrsticam;

CREATE TABLE IF NOT EXISTS vloga (
    vloga_id INT AUTO_INCREMENT PRIMARY KEY,
    naziv_vloge VARCHAR(50) NOT NULL,
    opis_vloge VARCHAR(255)
);


INSERT INTO vloga (naziv_vloge, opis_vloge) VALUES
('uporabnik', 'Navaden uporabnik z osnovnimi
 pravicami'),
('administrator', 'Uporabnik z administratorskimi pravicami');


CREATE TABLE IF NOT EXISTS uporabnik (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(50) NOT NULL,
    priimek VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    geslo VARCHAR(255) NOT NULL,
    datum_registracije DATE NOT NULL,
    vloga_id INT NOT NULL,
    FOREIGN KEY (vloga_id) REFERENCES vloga(vloga_id)
        ON DELETE RESTRICT -- Vloge, ki so v uporabi, se ne smejo zbrisati
        ON UPDATE CASCADE -- Vsi uporabniki ohranijo pravilno vlogo (posodobi, če se spremeni vloga)
        );

CREATE TABLE IF NOT EXISTS kategorija (
    kategorija_id INT AUTO_INCREMENT PRIMARY KEY,
    naziv_kategorije VARCHAR(100) NOT NULL,
    opis VARCHAR(255)
);

INSERT INTO kategorija (naziv_kategorije, opis) VALUES
('Tehnologija', 'Novosti in mnenja o tehnologiji'),
('Potovanja', 'Zgodbe in nasveti s poti'),
('Mnenja', 'Osebni pogledi in razmišljanja');

CREATE TABLE IF NOT EXISTS objava (
    objava_id INT AUTO_INCREMENT PRIMARY KEY,
    naslov VARCHAR(150) NOT NULL,
    vsebina TEXT NOT NULL,
    datum_objave DATE NOT NULL,
    uporabnik_id INT NOT NULL,
    kategorija_id INT NOT NULL,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabnik(user_id)
        ON DELETE CASCADE -- če se zbriše uporabnik, se zbrišejo vse njegove objave
        ON UPDATE CASCADE,
    FOREIGN KEY (kategorija_id) REFERENCES kategorija(kategorija_id)
        ON DELETE RESTRICT -- Če poskusiš izbrisati kategorijo, ki je že uporabljena: ne brisanje
        ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS komentar (
    komentar_id INT AUTO_INCREMENT PRIMARY KEY,
    vsebina TEXT NOT NULL,
    datum_komentarja DATE NOT NULL,
    uporabnik_id INT NOT NULL,
    objava_id INT NOT NULL,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabnik(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (objava_id) REFERENCES objava(objava_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS vsecek (
    vsecek_id INT AUTO_INCREMENT PRIMARY KEY,
    datum_vsecka DATE NOT NULL,
    uporabnik_id INT NOT NULL,
    objava_id INT NOT NULL,
    FOREIGN KEY (uporabnik_id) REFERENCES uporabnik(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (objava_id) REFERENCES objava(objava_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


INSERT INTO uporabnik (ime, priimek, email, geslo, datum_registracije, vloga_id)
VALUES
('Ana', 'Novak', 'ana.novak@example.com', 'geslo123', '2024-05-01', 1),
('Marko', 'Kralj', 'marko.kralj@example.com', 'geslo456', '2024-06-10', 2);

INSERT INTO objava (naslov, vsebina, datum_objave, uporabnik_id, kategorija_id)
VALUES
('Moj prvi blog', 'To je vsebina mojega prvega blog zapisa.', '2024-07-01', 1, 1),
('Potovanje v Italijo', 'Doživetja iz Firenc in Rima.', '2024-07-10', 1, 2);

INSERT INTO komentar (vsebina, datum_komentarja, uporabnik_id, objava_id)
VALUES
('Odličen zapis!', '2024-07-02', 2, 1),
('Zanimivo branje, hvala!', '2024-07-11', 2, 2);

INSERT INTO vsecek (datum_vsecka, uporabnik_id, objava_id)
VALUES
('2024-07-03', 2, 1),
('2024-07-12', 1, 2);
