import os
import subprocess
import time
from playwright.sync_api import sync_playwright

output_dir = "/mnt/c/Users/gaura/Documents/GitHub/ims_screenshots"
os.makedirs(output_dir, exist_ok=True)

host_ip = subprocess.check_output("ip route | grep default | awk '{print $3}'", shell=True).decode().strip()
base_url = f"http://{host_ip}:8000"

print(f"Connecting to {base_url} ...")

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(
        viewport={"width": 1440, "height": 1000},
        device_scale_factor=2,
    )
    page = context.new_page()
    page.set_default_timeout(15000)

    # 1. Login Page
    print("Capturing 01_login_page.png...")
    page.goto(f"{base_url}/login")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/01_login_page.png")

    # Perform Login
    page.fill('input[name="email"]', "admin@inventory.local")
    page.fill('input[name="password"]', "password")
    page.click('button[type="submit"]')
    page.wait_for_load_state("load")
    time.sleep(1)

    # 2. Dashboard
    print("Capturing 02_dashboard.png...")
    page.goto(f"{base_url}/dashboard")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/02_dashboard.png")

    # 3. Reports Page - HTML Pivot Table Tab
    print("Capturing 03_reports_pivot_table.png...")
    page.goto(f"{base_url}/reports/inventory")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/03_reports_pivot_table.png")

    # 4. Reports Page - Category Pivot Matrix
    print("Capturing 04_reports_pivot_by_category.png...")
    category_btn = page.locator('button:has-text("By Asset Category")')
    if category_btn.count() > 0:
        category_btn.click()
        time.sleep(1)
        page.screenshot(path=f"{output_dir}/04_reports_pivot_by_category.png")

    # 5. Reports Page - Live Asset Register Tab
    print("Capturing 05_reports_asset_register.png...")
    asset_reg_tab = page.locator('button:has-text("Live Asset Register")')
    if asset_reg_tab.count() > 0:
        asset_reg_tab.click()
        time.sleep(1)
        page.screenshot(path=f"{output_dir}/05_reports_asset_register.png")

    # 6. Reports Page - Consumable Stock Levels
    print("Capturing 06_reports_consumables.png...")
    consumables_tab = page.locator('button:has-text("Consumable Stock Levels")')
    if consumables_tab.count() > 0:
        consumables_tab.click()
        time.sleep(1)
        page.screenshot(path=f"{output_dir}/06_reports_consumables.png")

    # 7. Organization Hierarchy - Interactive Tree View
    print("Capturing 07_organization_hierarchy_tree.png...")
    page.goto(f"{base_url}/organization/hierarchy")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/07_organization_hierarchy_tree.png")

    # 8. Organization Hierarchy - Graph / Org Chart View
    print("Capturing 08_organization_hierarchy_graph.png...")
    graph_btn = page.locator('button:has-text("Org Chart / Graph")')
    if graph_btn.count() > 0:
        graph_btn.click()
        time.sleep(1)
        page.screenshot(path=f"{output_dir}/08_organization_hierarchy_graph.png")

    # 9. Assets Index
    print("Capturing 09_assets_index.png...")
    page.goto(f"{base_url}/assets")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/09_assets_index.png")

    # 10. Asset Show Detail
    print("Capturing 10_asset_details.png...")
    first_asset_link = page.locator('table tbody tr td a').first
    if first_asset_link.count() > 0:
        first_asset_link.click()
        time.sleep(1)
        page.screenshot(path=f"{output_dir}/10_asset_details.png")

    # 11. Consumables Index
    print("Capturing 11_consumables_index.png...")
    page.goto(f"{base_url}/consumables")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/11_consumables_index.png")

    # 12. Stock Ledger / History
    print("Capturing 12_stock_ledger.png...")
    page.goto(f"{base_url}/stock")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/12_stock_ledger.png")

    # 13. Locations / Rooms & Stores
    print("Capturing 13_locations.png...")
    page.goto(f"{base_url}/locations")
    time.sleep(1)
    page.screenshot(path=f"{output_dir}/13_locations.png")

    browser.close()

print(f"SUCCESS: All screenshots saved to: {output_dir}")
