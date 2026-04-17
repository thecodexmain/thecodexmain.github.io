import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "CryptoSwap - Sell USDT Securely",
  description: "Sell USDT with 100% Advance Payment Security. Trusted crypto exchange with verified clients, best rates, and zero risk.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body style={{ backgroundColor: "#0b0f1a", fontFamily: "system-ui, -apple-system, sans-serif" }}>
        {children}
      </body>
    </html>
  );
}
