<?php
declare(strict_types=1);

// === Einstellungen ===
$to = "sauer.phil@icloud.com";              // Empfänger (du)
$siteName = "Hausservice & Klimaservice";   // Website-Name (für Betreff/Antwort)
$maxLen = 5000;                             // maximale Zeichen in Nachricht

// Nur POST erlauben
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit("Method not allowed");
}

// Honeypot (Spam-Schutz): wenn ausgefüllt -> abbrechen
if (!empty($_POST["website"] ?? "")) {
  http_response_code(200);
  exit("OK");
}

// Eingaben holen & säubern
$name = trim((string)($_POST["name"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$ort = trim((string)($_POST["ort"] ?? ""));
$msg = trim((string)($_POST["nachricht"] ?? ""));

// Grundvalidierung
if ($name === "" || $email === "" || $msg === "") {
  header("Location: kontakt.html?status=missing");
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header("Location: kontakt.html?status=email");
  exit;
}

// Header-Injection verhindern
if (preg_match("/[\r\n]/", $email) || preg_match("/[\r\n]/", $name)) {
  header("Location: kontakt.html?status=bad");
  exit;
}

// Nachricht begrenzen
if (mb_strlen($msg) > $maxLen) {
  $msg = mb_substr($msg, 0, $maxLen) . "\n\n[gekürzt]";
}

// E-Mail Inhalt
$subject = "Neue Anfrage über Kontaktformular – " . $siteName;

$bodyLines = [
  "Neue Anfrage über das Kontaktformular",
  "-------------------------------------",
  "Name: " . $name,
  "E-Mail: " . $email,
  "Ort: " . ($ort !== "" ? $ort : "-"),
  "",
  "Nachricht:",
  $msg,
  "",
  "IP: " . ($_SERVER["REMOTE_ADDR"] ?? "-"),
  "Zeit: " . date("Y-m-d H:i:s"),
];

$body = implode("\n", $bodyLines);

// Header setzen
$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";

// Wichtig: From muss oft zur Domain passen, sonst Spam/Block
// Wir setzen From auf eine Domain-Adresse (wenn du die noch nicht hast, siehe Hinweis unten)
$from = "no-reply@" . (isset($_SERVER["HTTP_HOST"]) ? preg_replace('/^www\./', '', $_SERVER["HTTP_HOST"]) : "example.com");
$headers[] = "From: " . $siteName . " <" . $from . ">";

// Reply-To = der Nutzer (damit du direkt antworten kannst)
$headers[] = "Reply-To: " . $name . " <" . $email . ">";

// Senden
$ok = @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, implode("\r\n", $headers));

// Weiterleitung zurück zur Kontaktseite
if ($ok) {
  header("Location: kontakt.html?status=sent");
  exit;
} else {
  header("Location: kontakt.html?status=fail");
  exit;
}
