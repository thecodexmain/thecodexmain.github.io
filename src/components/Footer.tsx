"use client";
import { Zap, MessageCircle } from "lucide-react";

const quickLinks = ["How It Works", "Payment Methods", "Regions Served", "Why Choose Us", "Contact Us"];

export default function Footer() {
  return (
    <footer
      className="border-t py-12 px-6"
      style={{ borderColor: "rgba(255,255,255,0.06)" }}
    >
      <div className="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-10">
        {/* Brand */}
        <div>
          <div className="flex items-center gap-2 mb-4">
            <Zap className="text-cyan-400 w-5 h-5" />
            <span className="text-lg font-bold bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">
              CryptoSwap
            </span>
          </div>
          <p className="text-gray-500 text-sm leading-relaxed">
            Professional USDT exchange platform with advance payment guarantee. Trusted by hundreds of sellers across Asia.
          </p>
        </div>

        {/* Quick Links */}
        <div>
          <h4 className="text-white font-semibold mb-4">Quick Links</h4>
          <ul className="space-y-2">
            {quickLinks.map((link) => (
              <li key={link}>
                <a href="#" className="text-gray-500 hover:text-cyan-400 transition-colors text-sm">
                  {link}
                </a>
              </li>
            ))}
          </ul>
        </div>

        {/* Contact */}
        <div>
          <h4 className="text-white font-semibold mb-4">Contact</h4>
          <a
            href="https://t.me/CryptoSwap2026_Bot"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 text-cyan-400 hover:text-cyan-300 transition-colors text-sm"
          >
            <MessageCircle className="w-4 h-4" />
            @CryptoSwap2026_Bot
          </a>
          <p className="text-gray-600 text-xs mt-6 leading-relaxed">
            ⚠️ Warning: Only transact through our official Telegram channel. Beware of impersonators and scammers.
          </p>
        </div>
      </div>

      <div
        className="max-w-6xl mx-auto mt-10 pt-6 border-t text-center"
        style={{ borderColor: "rgba(255,255,255,0.04)" }}
      >
        <p className="text-gray-600 text-xs">
          © {new Date().getFullYear()} CryptoSwap. All rights reserved. — Advance payment model for verified sellers only.
        </p>
      </div>
    </footer>
  );
}
