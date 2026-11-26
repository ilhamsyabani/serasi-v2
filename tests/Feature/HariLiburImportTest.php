<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\HariLibur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class HariLiburImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = $this->user(Role::ADMIN_IT);
    }

    private function user(string $kode): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' Uji',
            'email' => uniqid() . '@bbpom.test',
            'no_whatsapp' => '0811' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('libur.csv', $isi);
    }

    /** Berkas .xlsx sungguhan; nilai DateTime ditulis sebagai sel bertipe Date. */
    private function xlsx(array $baris): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hl') . '.xlsx';
        $writer = new XlsxWriter();
        $writer->openToFile($path);
        foreach ($baris as $b) {
            $writer->addRow(Row::fromValues($b));
        }
        $writer->close();

        return new UploadedFile($path, 'libur.xlsx', null, null, true);
    }

    private function import(UploadedFile $berkas, array $extra = [])
    {
        return $this->actingAs($this->admin)
            ->post(route('internal.adminit.hari-libur.import.store'), ['berkas' => $berkas] + $extra);
    }

    public function test_halaman_hari_libur_memuat_modal_import_dan_dashboard_punya_tombolnya(): void
    {
        $this->actingAs($this->admin)->get(route('internal.adminit.hari-libur.index'))
            ->assertOk()->assertSee('Import Hari Libur dari Excel');

        $this->actingAs($this->admin)->get(route('internal.adminit.dashboard'))
            ->assertOk()->assertSee(route('internal.adminit.hari-libur.index', ['import' => 1]), false);
    }

    public function test_role_lain_tidak_boleh_import(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $this->actingAs($katim)->post(route('internal.adminit.hari-libur.import.store'), [
            'berkas' => $this->csv("2027-01-01;Tahun Baru\n"),
        ])->assertForbidden();
        $this->actingAs($katim)->get(route('internal.adminit.hari-libur.import.template'))->assertForbidden();
        $this->assertSame(0, HariLibur::count());
    }

    public function test_import_excel_dengan_sel_date_dan_teks(): void
    {
        $this->import($this->xlsx([
            ['Tanggal', 'Keterangan'],
            [new \DateTimeImmutable('2027-01-01'), 'Tahun Baru Masehi'],
            ['17/08/2027', 'Hari Kemerdekaan RI'],
            ['2027-12-25', 'Natal'],
        ]))->assertRedirect(route('internal.adminit.hari-libur.index'))
           ->assertSessionHas('hasilImport', fn ($h) => $h['ditambah'] === 3 && $h['gagal'] === []);

        $this->assertEqualsCanonicalizing(
            ['2027-01-01', '2027-08-17', '2027-12-25'],
            HariLibur::pluck('tanggal')->map->toDateString()->all(),
        );
        $this->assertTrue(AuditTrail::where('aksi', 'import_hari_libur')->where('modul', 'M-16')->exists());
    }

    public function test_import_excel_melaporkan_nomor_baris_yang_gagal(): void
    {
        $this->import($this->xlsx([
            ['Tanggal', 'Keterangan'],
            ['2027-01-01', 'Tahun Baru'],
            ['31/02/2027', 'Tanggal mustahil'],
        ]))->assertSessionHas('hasilImport', fn ($h) => $h['ditambah'] === 1
            && $h['gagal'] === ['Baris 3: tanggal "31/02/2027" tidak valid.']);
    }

    public function test_import_csv_titik_koma_dengan_header_dan_bom(): void
    {
        $isi = "\xEF\xBB\xBFtanggal;keterangan\r\n2027-01-01;Tahun Baru Masehi\r\n\r\n25-12-2027;Natal\r\n";

        $this->import($this->csv($isi))
            ->assertSessionHas('hasilImport', fn ($h) => $h['ditambah'] === 2 && $h['gagal'] === []);
    }

    public function test_baris_tidak_valid_dilaporkan_dan_baris_valid_tetap_masuk(): void
    {
        $isi = "2027-01-01,Tahun Baru\n31/02/2027,Tanggal mustahil\nbukan-tanggal,X\n2027-03-01,\n2027-01-01,Ganda di berkas\n";

        $this->import($this->csv($isi))
            ->assertSessionHas('hasilImport', fn ($h) => $h['ditambah'] === 1 && count($h['gagal']) === 4);

        $this->assertSame(1, HariLibur::count());
    }

    public function test_tanggal_yang_sudah_ada_dilewati_atau_ditimpa(): void
    {
        HariLibur::create(['tanggal' => '2027-01-01', 'keterangan' => 'Lama']);

        $this->import($this->csv("2027-01-01;Baru\n"))
            ->assertSessionHas('hasilImport', fn ($h) => $h['dilewati'] === ['2027-01-01']);
        $this->assertSame('Lama', HariLibur::first()->keterangan);

        $this->import($this->csv("2027-01-01;Baru\n"), ['timpa' => '1'])
            ->assertSessionHas('hasilImport', fn ($h) => $h['diperbarui'] === 1);
        $this->assertSame('Baru', HariLibur::first()->keterangan);
    }

    public function test_berkas_selain_excel_csv_ditolak(): void
    {
        $this->import(UploadedFile::fake()->create('libur.pdf', 10, 'application/pdf'))
            ->assertSessionHasErrors('berkas');
    }

    public function test_template_excel_bisa_diunduh(): void
    {
        $this->actingAs($this->admin)->get(route('internal.adminit.hari-libur.import.template'))
            ->assertOk()->assertDownload('template-hari-libur.xlsx');
    }
}
