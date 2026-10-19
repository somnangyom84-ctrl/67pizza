import type { Metadata } from "next";
import type { ReactNode } from "react";
import "./globals.css";

export const metadata: Metadata = {
  title: "67pizza – Delicious Food Delivered Fresh",
  description:
    "Design preview of the 67pizza PHP + MySQL food ordering platform. The full project lives in the 67pizza/ folder, ready for XAMPP.",
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="en">
      <body className="bg-[#0d0d0f] text-white antialiased">{children}</body>
    </html>
  );
}
