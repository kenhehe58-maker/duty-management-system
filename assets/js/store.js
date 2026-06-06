const AppStore = {
    data: {
        schedule: [],
        students: [],
        rules: {}
    },

    init: async function() {
        console.log("AppStore đã khởi tạo thành công!");
        // Gọi API để lấy dữ liệu ban đầu
        await this.fetchData();
    },

    fetchData: async function() {
        // Ví dụ: lấy dữ liệu từ các file API bạn đã tạo
        try {
            const res = await fetch('modules/schedule.php');
            this.data.schedule = await res.json();
        } catch (e) {
            console.error("Lỗi tải dữ liệu:", e);
        }
    },

    // Các hàm đang bị báo lỗi, bạn cần định nghĩa chúng tại đây:
    reorderDuty: function(data) {
        console.log("Đang reorder:", data);
        // Sau khi logic xong, gọi API để lưu vào MySQL
    },

    removeDutyPerson: function(personId) {
        console.log("Đang xóa:", personId);
        // Gửi fetch('api/schedule.php', {method: 'POST', ...}) để update DB
    },

    addExtraDay: function() {
        console.log("Đang thêm ngày trực...");
    }
};